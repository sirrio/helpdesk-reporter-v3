<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserIndexRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index(AdminUserIndexRequest $request): Response
    {
        $filters = $request->safe()->only([
            'search',
            'role',
            'status',
        ]);
        $systemSettings = SystemSetting::current();
        $anonymizationMonths = $systemSettings->anonymizationMonths();
        $anonymizationCutoff = now()->subMonthsNoOverflow($anonymizationMonths);

        $users = User::query()
            ->withTrashed()
            ->withCount('attendances')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters): void {
                    $builder
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%');
                }),
            )
            ->when(
                ($filters['role'] ?? null) === 'admin',
                fn ($query) => $query->where('isAdmin', true),
            )
            ->when(
                ($filters['role'] ?? null) === 'mod',
                fn ($query) => $query
                    ->where('isMod', true)
                    ->where('isAdmin', false),
            )
            ->when(
                ($filters['role'] ?? null) === 'tutor',
                fn ($query) => $query
                    ->where('isMod', false)
                    ->where('isAdmin', false),
            )
            ->when(
                ($filters['status'] ?? null) === 'active',
                fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->whereNotNull('approved_at'),
            )
            ->when(
                ($filters['status'] ?? null) === 'pending',
                fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->whereNull('approved_at'),
            )
            ->when(
                ($filters['status'] ?? null) === 'deactivated',
                fn ($query) => $query
                    ->onlyTrashed()
                    ->whereNull('anonymized_at'),
            )
            ->when(
                ($filters['status'] ?? null) === 'anonymized',
                fn ($query) => $query->whereNotNull('anonymized_at'),
            )
            ->orderByDesc('isAdmin')
            ->orderByDesc('isMod')
            ->orderBy('approved_at')
            ->orderBy('deleted_at')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isMod' => $user->isMod,
                'isAdmin' => $user->isAdmin,
                'approvedAt' => $user->approved_at?->toISOString(),
                'mustChangePassword' => $user->must_change_password,
                'createdAt' => $user->created_at?->toISOString(),
                'attendancesCount' => $user->attendances_count,
                'isCurrentUser' => $request->user()->is($user),
                'deletedAt' => $user->deleted_at?->toISOString(),
                'anonymizedAt' => $user->anonymized_at?->toISOString(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'automation' => [
                'anonymizationMonths' => $anonymizationMonths,
                'pendingAnonymizationCount' => User::onlyTrashed()
                    ->whereNull('anonymized_at')
                    ->where('deleted_at', '<=', $anonymizationCutoff)
                    ->count(),
                'scheduler' => $this->heartbeatStatus($systemSettings->scheduler_heartbeat_at),
                'queue' => [
                    ...$this->heartbeatStatus($systemSettings->queue_heartbeat_at),
                    'connection' => (string) config('queue.default'),
                ],
                'healthCheck' => $this->automationCheckStatus($systemSettings),
            ],
        ]);
    }

    /**
     * Store a newly created managed user.
     */
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'isMod' => (bool) ($validated['isMod'] || $validated['isAdmin']),
            'isAdmin' => (bool) $validated['isAdmin'],
            'approved_at' => now(),
            'must_change_password' => true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Benutzer angelegt.'),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Update an existing managed user.
     */
    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $passwordWasReset = filled($validated['password'] ?? null);

        DB::transaction(function () use ($user, $validated, $passwordWasReset): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'isMod' => (bool) ($validated['isMod'] || $validated['isAdmin']),
                'isAdmin' => (bool) $validated['isAdmin'],
            ]);

            if ($passwordWasReset) {
                $user->forceFill([
                    'password' => $validated['password'],
                    'must_change_password' => true,
                    'remember_token' => null,
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ]);
            }

            $user->save();

            if ($passwordWasReset) {
                $this->deleteSessionsFor($user);
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $passwordWasReset
                ? __('Benutzer aktualisiert. Das temporäre Passwort muss beim nächsten Login geändert werden.')
                : __('Benutzer aktualisiert.'),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Deactivate a managed user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if(
            $request->user()->is($user),
            403,
            'Du kannst deinen eigenen Account nicht deaktivieren.',
        );

        DB::transaction(function () use ($user): void {
            $user->setRememberToken(null);
            $user->save();
            $user->delete();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table'))
                    ->where('user_id', $user->getKey())
                    ->delete();
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Benutzer deaktiviert.'),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Reactivate a managed user.
     */
    public function restore(int $user): RedirectResponse
    {
        $managedUser = User::withTrashed()->findOrFail($user);

        abort_if(
            $managedUser->anonymized_at !== null,
            409,
            'Ein anonymisierter Account kann nicht reaktiviert werden.',
        );

        $managedUser->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Benutzer reaktiviert.'),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Approve a newly registered user account.
     */
    public function approve(User $user): RedirectResponse
    {
        $user->update(['approved_at' => now()]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Benutzer freigeschaltet.'),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Remove all database-backed sessions for a managed user.
     */
    private function deleteSessionsFor(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->getKey())
            ->delete();
    }

    /**
     * Build the health status for an automation heartbeat.
     *
     * @return array{status: 'healthy'|'stale'|'unknown', lastSeenAt: string|null}
     */
    private function heartbeatStatus(?CarbonInterface $lastSeenAt): array
    {
        $status = match (true) {
            $lastSeenAt === null => 'unknown',
            $lastSeenAt->gte(now()->subMinutes(SystemSetting::HEARTBEAT_GRACE_MINUTES)) => 'healthy',
            default => 'stale',
        };

        return [
            'status' => $status,
            'lastSeenAt' => $lastSeenAt?->toISOString(),
        ];
    }

    /**
     * Build the status of the latest end-to-end automation check.
     *
     * @return array{status: 'idle'|'pending'|'passed'|'failed', requestedAt: string|null, completedAt: string|null}
     */
    private function automationCheckStatus(SystemSetting $settings): array
    {
        $requestedAt = $settings->automation_check_requested_at;
        $completedAt = $settings->automation_check_completed_at;
        $requestedToken = $settings->automation_check_token;
        $completedToken = $settings->automation_check_completed_token;

        $status = match (true) {
            $requestedAt === null || $requestedToken === null => 'idle',
            $completedAt !== null && $completedToken === $requestedToken => 'passed',
            $requestedAt->gte(now()->subMinutes(SystemSetting::HEARTBEAT_GRACE_MINUTES)) => 'pending',
            default => 'failed',
        };

        return [
            'status' => $status,
            'requestedAt' => $requestedAt?->toISOString(),
            'completedAt' => $completedAt?->toISOString(),
        ];
    }
}
