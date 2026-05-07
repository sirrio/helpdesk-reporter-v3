<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminDegreeIndexRequest;
use App\Http\Requests\StoreDegreeRequest;
use App\Http\Requests\UpdateDegreeRequest;
use App\Models\Attendance;
use App\Models\Degree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDegreeController extends Controller
{
    /**
     * Display the degree management page.
     */
    public function index(AdminDegreeIndexRequest $request): Response
    {
        $filters = $request->safe()->only(['status']);

        $degrees = Degree::query()
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
            ->through(fn (Degree $degree) => [
                'id' => $degree->id,
                'name' => $degree->name,
                'attendancesCount' => $degree->attendances_count,
                'deletedAt' => $degree->deleted_at?->toISOString(),
            ]);

        return Inertia::render('admin/degrees/index', [
            'degrees' => $degrees,
            'filters' => [
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    /**
     * Store a new degree.
     */
    public function store(StoreDegreeRequest $request): RedirectResponse
    {
        Degree::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang angelegt.'),
        ]);

        return to_route('admin.degrees.index');
    }

    /**
     * Update a degree and keep attendance snapshots in sync.
     */
    public function update(UpdateDegreeRequest $request, Degree $degree): RedirectResponse
    {
        $validated = $request->validated();
        $originalName = $degree->name;

        DB::transaction(function () use ($degree, $validated, $originalName): void {
            if ($validated['name'] !== $originalName) {
                Attendance::query()
                    ->where('degree', $originalName)
                    ->update(['degree' => $validated['name']]);
            }

            $degree->update($validated);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang aktualisiert.'),
        ]);

        return to_route('admin.degrees.index');
    }

    /**
     * Archive a degree.
     */
    public function destroy(Degree $degree): RedirectResponse
    {
        $degree->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang archiviert.'),
        ]);

        return to_route('admin.degrees.index');
    }

    /**
     * Restore an archived degree.
     */
    public function restore(int $degree): RedirectResponse
    {
        Degree::withTrashed()->findOrFail($degree)->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Studiengang wiederhergestellt.'),
        ]);

        return to_route('admin.degrees.index');
    }
}
