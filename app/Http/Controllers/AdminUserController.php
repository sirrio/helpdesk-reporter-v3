<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserIndexRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
        ]);

        $users = User::query()
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
            ->orderByDesc('isAdmin')
            ->orderByDesc('isMod')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isMod' => $user->isMod,
                'isAdmin' => $user->isAdmin,
                'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
                'createdAt' => $user->created_at?->toISOString(),
                'attendancesCount' => $user->attendances_count,
                'isCurrentUser' => $request->user()->is($user),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
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
            'email_verified_at' => now(),
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

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'isMod' => (bool) ($validated['isMod'] || $validated['isAdmin']),
            'isAdmin' => (bool) $validated['isAdmin'],
            'email_verified_at' => $user->email === $validated['email']
                ? ($user->email_verified_at ?? now())
                : now(),
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Benutzer aktualisiert.'),
        ]);

        return to_route('admin.users.index');
    }
}
