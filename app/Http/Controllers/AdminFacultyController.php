<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminFacultyIndexRequest;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Models\Attendance;
use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminFacultyController extends Controller
{
    /**
     * Display the faculty management page.
     */
    public function index(AdminFacultyIndexRequest $request): Response
    {
        $filters = $request->safe()->only(['status']);

        $faculties = Faculty::query()
            ->withTrashed()
            ->withCount('attendances')
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
            ->withQueryString()
            ->through(fn (Faculty $faculty) => [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'attendancesCount' => $faculty->attendances_count,
                'deletedAt' => $faculty->deleted_at?->toISOString(),
            ]);

        return Inertia::render('admin/faculties/index', [
            'faculties' => $faculties,
            'filters' => [
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    /**
     * Store a new faculty.
     */
    public function store(StoreFacultyRequest $request): RedirectResponse
    {
        Faculty::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Fachbereich angelegt.'),
        ]);

        return to_route('admin.faculties.index');
    }

    /**
     * Update a faculty and keep attendance snapshots in sync.
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validated();
        $originalName = $faculty->name;

        DB::transaction(function () use ($faculty, $validated, $originalName): void {
            if ($validated['name'] !== $originalName) {
                Attendance::query()
                    ->where('faculty', $originalName)
                    ->update(['faculty' => $validated['name']]);
            }

            $faculty->update($validated);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Fachbereich aktualisiert.'),
        ]);

        return to_route('admin.faculties.index');
    }

    /**
     * Archive a faculty.
     */
    public function destroy(Faculty $faculty): RedirectResponse
    {
        $faculty->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Fachbereich archiviert.'),
        ]);

        return to_route('admin.faculties.index');
    }

    /**
     * Restore an archived faculty.
     */
    public function restore(int $faculty): RedirectResponse
    {
        Faculty::withTrashed()->findOrFail($faculty)->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Fachbereich wiederhergestellt.'),
        ]);

        return to_route('admin.faculties.index');
    }
}
