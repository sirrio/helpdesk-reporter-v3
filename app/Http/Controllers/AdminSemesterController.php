<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminSemesterIndexRequest;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use App\Models\Attendance;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminSemesterController extends Controller
{
    /**
     * Display the semester management page.
     */
    public function index(AdminSemesterIndexRequest $request): Response
    {
        $filters = $request->safe()->only(['status']);

        $semesters = Semester::query()
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
            ->orderByDesc('start')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Semester $semester) => [
                'id' => $semester->id,
                'semester' => $semester->semester,
                'start' => $semester->start?->toDateString(),
                'end' => $semester->end?->toDateString(),
                'attendancesCount' => $semester->attendances_count,
                'deletedAt' => $semester->deleted_at?->toISOString(),
            ]);

        return Inertia::render('admin/semesters/index', [
            'semesters' => $semesters,
            'filters' => [
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    /**
     * Store a new semester.
     */
    public function store(StoreSemesterRequest $request): RedirectResponse
    {
        Semester::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Semester angelegt.'),
        ]);

        return to_route('admin.semesters.index');
    }

    /**
     * Update an existing semester and its attendance references.
     */
    public function update(UpdateSemesterRequest $request, Semester $semester): RedirectResponse
    {
        $validated = $request->validated();
        $originalLabel = $semester->semester;

        DB::transaction(function () use ($semester, $validated, $originalLabel): void {
            if ($validated['semester'] !== $originalLabel) {
                Attendance::query()
                    ->where('semester', $originalLabel)
                    ->update(['semester' => $validated['semester']]);
            }

            $semester->update($validated);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Semester aktualisiert.'),
        ]);

        return to_route('admin.semesters.index');
    }

    /**
     * Archive a semester.
     */
    public function destroy(Semester $semester): RedirectResponse
    {
        $semester->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Semester archiviert.'),
        ]);

        return to_route('admin.semesters.index');
    }

    /**
     * Restore an archived semester.
     */
    public function restore(int $semester): RedirectResponse
    {
        Semester::withTrashed()->findOrFail($semester)->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Semester wiederhergestellt.'),
        ]);

        return to_route('admin.semesters.index');
    }
}
