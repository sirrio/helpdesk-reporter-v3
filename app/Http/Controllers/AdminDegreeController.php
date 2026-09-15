<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminDegreeIndexRequest;
use App\Http\Requests\StoreDegreeRequest;
use App\Http\Requests\UpdateDegreeRequest;
use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminDegreeController extends Controller
{
    /**
     * Display the degree management page.
     */
    public function index(AdminDegreeIndexRequest $request): Response|RedirectResponse
    {
        $filters = $request->safe()->only(['status']);

        $degrees = Degree::query()
            ->withTrashed()
            ->with('faculty:id,name')
            ->withCount('attendances')
            ->withExists(['attendances as has_attendance_history' => fn ($query) => $query->withTrashed()])
            ->when(
                ($filters['status'] ?? 'all') === 'active',
                fn ($query) => $query->whereNull('deleted_at'),
            )
            ->when(
                ($filters['status'] ?? null) === 'archived',
                fn ($query) => $query->onlyTrashed(),
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        if ($degrees->currentPage() > $degrees->lastPage()) {
            return to_route('admin.degrees.index', [
                ...$filters,
                'page' => $degrees->lastPage(),
            ]);
        }

        $degrees->through(fn (Degree $degree) => [
            'id' => $degree->id,
            'name' => $degree->name,
            'facultyId' => $degree->faculty_id,
            'faculty' => $degree->faculty?->name,
            'attendancesCount' => $degree->attendances_count,
            'deletedAt' => $degree->deleted_at?->toISOString(),
            'canDelete' => $degree->trashed() && ! $degree->has_attendance_history,
        ]);

        return Inertia::render('admin/degrees/index', [
            'degrees' => $degrees,
            'filters' => [
                'status' => $filters['status'] ?? '',
            ],
            'faculties' => Faculty::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a new degree.
     */
    public function store(StoreDegreeRequest $request): RedirectResponse
    {
        $context = AdminDegreeIndexRequest::queryContext($request);

        Degree::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang angelegt.'),
        ]);

        return to_route('admin.degrees.index', $context);
    }

    /**
     * Update a degree and keep attendance snapshots in sync.
     */
    public function update(UpdateDegreeRequest $request, Degree $degree): RedirectResponse
    {
        $context = AdminDegreeIndexRequest::queryContext($request);
        $validated = $request->validated();
        $validated['faculty_id'] = (int) $validated['faculty_id'];
        $originalName = $degree->name;
        $facultyName = Faculty::query()->findOrFail($validated['faculty_id'])->name;

        DB::transaction(function () use ($degree, $validated, $originalName, $facultyName): void {
            if ($validated['name'] !== $originalName || $degree->faculty_id !== $validated['faculty_id']) {
                Attendance::withTrashed()
                    ->where('degree', $originalName)
                    ->update([
                        'degree' => $validated['name'],
                        'faculty' => $facultyName,
                    ]);
            }

            $degree->update($validated);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang aktualisiert.'),
        ]);

        return to_route('admin.degrees.index', $context);
    }

    /**
     * Archive a degree.
     */
    public function destroy(AdminDegreeIndexRequest $request, Degree $degree): RedirectResponse
    {
        $degree->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang archiviert.'),
        ]);

        return to_route('admin.degrees.index', $request->validated());
    }

    /**
     * Restore an archived degree.
     */
    public function restore(AdminDegreeIndexRequest $request, int $degree): RedirectResponse
    {
        Degree::withTrashed()->findOrFail($degree)->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang wiederhergestellt.'),
        ]);

        return to_route('admin.degrees.index', $request->validated());
    }

    public function forceDestroy(AdminDegreeIndexRequest $request, int $degree): RedirectResponse
    {
        DB::transaction(function () use ($degree): void {
            $degree = Degree::withTrashed()->lockForUpdate()->findOrFail($degree);

            if (! $degree->trashed()) {
                throw ValidationException::withMessages([
                    'degree' => __('Nur archivierte Studiengänge können endgültig gelöscht werden.'),
                ]);
            }

            if ($degree->attendances()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'degree' => __('Studiengänge mit zugeordneten Einsätzen können nicht gelöscht werden.'),
                ]);
            }

            $degree->forceDelete();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang endgültig gelöscht.'),
        ]);

        return to_route('admin.degrees.index', $request->validated());
    }
}
