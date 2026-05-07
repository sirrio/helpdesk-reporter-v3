<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceIndexRequest;
use App\Http\Requests\AttendanceIndexRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Show the current tutor's helpdesk entries.
     */
    public function index(AttendanceIndexRequest $request): Response
    {
        $filters = $request->safe()->only($this->attendanceFilterKeys());
        $formState = $this->attendanceFormState();

        return Inertia::render('attendances/index', [
            'attendances' => $this->paginateAttendances(
                $request->user()->attendances()->filter($filters),
            ),
            'filters' => $this->presentFilters($filters),
            'formOptions' => $formState['options'],
            'canCreateEntries' => $formState['canCreateEntries'],
        ]);
    }

    /**
     * Show every recorded helpdesk entry for administrators.
     */
    public function adminIndex(AdminAttendanceIndexRequest $request): Response
    {
        $filters = $request->safe()->only([
            ...$this->attendanceFilterKeys(),
            'user',
        ]);
        $formState = $this->attendanceFormState();

        return Inertia::render('admin/attendances/index', [
            'attendances' => $this->paginateAttendances(
                Attendance::query()
                    ->with('user:id,name,email')
                    ->filter($filters)
                    ->when(
                        filled($filters['user'] ?? null),
                        fn ($query) => $query->where('user_id', $filters['user']),
                    ),
                includeTutor: true,
            ),
            'filters' => [
                ...$this->presentFilters($filters),
                'user' => array_key_exists('user', $filters)
                    ? (string) $filters['user']
                    : '',
            ],
            'formOptions' => $this->adminAttendanceFormOptions($formState['options']),
        ]);
    }

    /**
     * Show aggregated helpdesk statistics for administrators.
     */
    public function statistics(AdminAttendanceIndexRequest $request): Response
    {
        $filters = $request->safe()->only([
            ...$this->attendanceFilterKeys(),
            'user',
        ]);
        $selectedWeek = $this->resolveSelectedStatisticsWeek(
            $request->safe()->only(['week'])['week'] ?? null,
            $filters,
        );
        $attendances = Attendance::query()
            ->filter($filters)
            ->when(
                filled($filters['user'] ?? null),
                fn ($query) => $query->where('user_id', $filters['user']),
            )
            ->orderByDesc('date')
            ->orderByDesc('startTime')
            ->get();

        return Inertia::render('admin/statistics/index', [
            'filters' => [
                ...$this->presentFilters($filters),
                'user' => array_key_exists('user', $filters)
                    ? (string) $filters['user']
                    : '',
            ],
            'week' => $selectedWeek->toDateString(),
            'formOptions' => $this->adminAttendanceFormOptions(
                $this->attendanceFormState()['options'],
            ),
            'stats' => $this->buildAttendanceStatistics($attendances, $selectedWeek),
        ]);
    }

    /**
     * Store a new helpdesk entry for the current tutor.
     */
    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $topicColumns = collect(Attendance::topicOptions())
            ->mapWithKeys(fn (string $label, string $column) => [$column => false]);
        $selectedTopics = collect($validated['topics'])
            ->mapWithKeys(fn (string $column) => [$column => true]);

        $request->user()->attendances()->create([
            ...$topicColumns->all(),
            ...$selectedTopics->all(),
            'semester' => $validated['semester'],
            'date' => $validated['date'],
            'startTime' => CarbonImmutable::createFromFormat('H:i', $validated['startTime'])->format('H:i:s'),
            'endTime' => CarbonImmutable::createFromFormat('H:i', $validated['endTime'])->format('H:i:s'),
            'degree' => $validated['degree'],
            'faculty' => $validated['faculty'],
            'online' => (bool) $validated['online'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Helpdesk-Eintrag gespeichert.'),
        ]);

        return to_route('attendances.index');
    }

    /**
     * Get the reusable attendance filter keys.
     *
     * @return array<int, string>
     */
    private function attendanceFilterKeys(): array
    {
        return [
            'semester',
            'degree',
            'faculty',
            'topic',
            'online',
            'from',
            'until',
        ];
    }

    /**
     * Get common select options used by attendance pages.
     *
     * @return array{options: array<string, mixed>, canCreateEntries: bool}
     */
    private function attendanceFormState(): array
    {
        $semesters = Semester::query()
            ->orderByDesc('start')
            ->pluck('semester')
            ->all();
        $degrees = Degree::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $faculties = Faculty::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return [
            'options' => [
                'semesters' => $semesters,
                'degrees' => $degrees,
                'faculties' => $faculties,
                'topics' => collect(Attendance::topicOptions())
                    ->map(fn (string $label, string $value) => [
                        'value' => $value,
                        'label' => $label,
                    ])
                    ->values()
                    ->all(),
            ],
            'canCreateEntries' => $semesters !== [] && $degrees !== [] && $faculties !== [],
        ];
    }

    /**
     * Normalize filter values for the frontend.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    private function presentFilters(array $filters): array
    {
        return [
            'semester' => $filters['semester'] ?? '',
            'degree' => $filters['degree'] ?? '',
            'faculty' => $filters['faculty'] ?? '',
            'topic' => $filters['topic'] ?? '',
            'online' => array_key_exists('online', $filters)
                ? (string) (int) (bool) $filters['online']
                : '',
            'from' => $filters['from'] ?? '',
            'until' => $filters['until'] ?? '',
        ];
    }

    /**
     * Get admin filter options including tutor choices.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function adminAttendanceFormOptions(array $options): array
    {
        return [
            ...$options,
            'tutors' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'value' => (string) $user->id,
                    'label' => $user->name,
                    'email' => $user->email,
                ])
                ->all(),
        ];
    }

    /**
     * Build aggregated statistics from a filtered attendance collection.
     *
     * @param  \Illuminate\Support\Collection<int, Attendance>  $attendances
     * @return array<string, mixed>
     */
    private function buildAttendanceStatistics($attendances, CarbonImmutable $selectedWeek): array
    {
        $entries = $attendances->count();
        $totalMinutes = $attendances->sum(
            fn (Attendance $attendance) => $this->attendanceDurationInMinutes($attendance),
        );
        $onlineEntries = $attendances->where('online', true)->count();
        $presenceEntries = $entries - $onlineEntries;
        $weeklySummary = $this->buildWeeklySummary($attendances, $selectedWeek);

        $topicBreakdown = collect(Attendance::topicOptions())
            ->map(function (string $label, string $column) use ($attendances): array {
                $count = $attendances->where($column, true)->count();

                return [
                    'key' => $column,
                    'label' => $label,
                    'entries' => $count,
                ];
            })
            ->filter(fn (array $topic): bool => $topic['entries'] > 0)
            ->sortByDesc('entries')
            ->values()
            ->all();

        $facultyBreakdown = $attendances
            ->groupBy('faculty')
            ->map(fn ($items, string $faculty): array => [
                'label' => $faculty,
                'entries' => $items->count(),
            ])
            ->sortByDesc('entries')
            ->values()
            ->all();

        $degreeBreakdown = $attendances
            ->groupBy('degree')
            ->map(fn ($items, string $degree): array => [
                'label' => $degree,
                'entries' => $items->count(),
            ])
            ->sortByDesc('entries')
            ->values()
            ->all();

        return [
            'totals' => [
                'entries' => $entries,
                'minutes' => $totalMinutes,
                'hours' => round($totalMinutes / 60, 1),
                'activeTutors' => $attendances
                    ->pluck('user_id')
                    ->filter()
                    ->unique()
                    ->count(),
                'semesters' => $attendances
                    ->pluck('semester')
                    ->filter()
                    ->unique()
                    ->count(),
                'onlineEntries' => $onlineEntries,
                'presenceEntries' => $presenceEntries,
                'onlinePercentage' => $entries > 0
                    ? round(($onlineEntries / $entries) * 100)
                    : 0,
            ],
            'weekly' => $weeklySummary,
            'faculties' => $facultyBreakdown,
            'degrees' => $degreeBreakdown,
            'topics' => $topicBreakdown,
        ];
    }

    /**
     * Resolve the selected statistics week from the request or filtered data.
     *
     * @param  array<string, mixed>  $filters
     */
    private function resolveSelectedStatisticsWeek(?string $week, array $filters): CarbonImmutable
    {
        if (filled($week)) {
            return CarbonImmutable::createFromFormat('Y-m-d', $week)->startOfWeek();
        }

        /** @var Attendance|null $latestAttendance */
        $latestAttendance = Attendance::query()
            ->filter($filters)
            ->when(
                filled($filters['user'] ?? null),
                fn ($query) => $query->where('user_id', $filters['user']),
            )
            ->orderByDesc('date')
            ->first();

        return $latestAttendance?->date?->startOfWeek() ?? CarbonImmutable::today()->startOfWeek();
    }

    /**
     * Build the selected week's daily overview.
     *
     * @param  \Illuminate\Support\Collection<int, Attendance>  $attendances
     * @return array<string, mixed>
     */
    private function buildWeeklySummary($attendances, CarbonImmutable $selectedWeek): array
    {
        $weekStart = $selectedWeek->startOfWeek();
        $weekEnd = $selectedWeek->endOfWeek();
        $weekdayLabels = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
        $weekAttendances = $attendances->filter(
            fn (Attendance $attendance): bool => $attendance->date->betweenIncluded($weekStart, $weekEnd),
        );

        return [
            'current' => $weekStart->toDateString(),
            'previous' => $weekStart->subWeek()->toDateString(),
            'next' => $weekStart->addWeek()->toDateString(),
            'label' => sprintf(
                'KW %s',
                $weekStart->isoWeek(),
            ),
            'rangeLabel' => sprintf(
                '%s - %s',
                $weekStart->format('d.m.Y'),
                $weekEnd->format('d.m.Y'),
            ),
            'totalEntries' => $weekAttendances->count(),
            'days' => collect(range(0, 6))
                ->map(function (int $offset) use ($weekStart, $weekAttendances, $weekdayLabels): array {
                    $day = $weekStart->addDays($offset);

                    return [
                        'label' => $weekdayLabels[$offset],
                        'date' => $day->toDateString(),
                        'entries' => $weekAttendances
                            ->filter(fn (Attendance $attendance): bool => $attendance->date->isSameDay($day))
                            ->count(),
                    ];
                })
                ->all(),
        ];
    }

    /**
     * Calculate the duration of an attendance entry in minutes.
     */
    private function attendanceDurationInMinutes(Attendance $attendance): int
    {
        return CarbonImmutable::createFromFormat('H:i:s', $attendance->startTime)
            ->diffInMinutes(
                CarbonImmutable::createFromFormat('H:i:s', $attendance->endTime),
            );
    }

    /**
     * Paginate attendances for Inertia responses.
     */
    private function paginateAttendances($query, bool $includeTutor = false)
    {
        return $query
            ->orderByDesc('date')
            ->orderByDesc('startTime')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Attendance $attendance) => $this->transformAttendance(
                $attendance,
                includeTutor: $includeTutor,
            ));
    }

    /**
     * Transform an attendance model for the frontend.
     *
     * @return array<string, mixed>
     */
    private function transformAttendance(Attendance $attendance, bool $includeTutor = false): array
    {
        return [
            'id' => $attendance->id,
            'date' => $attendance->date->toDateString(),
            'startTime' => CarbonImmutable::parse($attendance->startTime)->format('H:i'),
            'endTime' => CarbonImmutable::parse($attendance->endTime)->format('H:i'),
            'semester' => $attendance->semester,
            'degree' => $attendance->degree,
            'faculty' => $attendance->faculty,
            'online' => $attendance->online,
            'topics' => $attendance->topicLabels(),
            'tutor' => $includeTutor
                ? [
                    'id' => $attendance->user?->id,
                    'name' => $attendance->user?->name,
                    'email' => $attendance->user?->email,
                ]
                : null,
        ];
    }
}
