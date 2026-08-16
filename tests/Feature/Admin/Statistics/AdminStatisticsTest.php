<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows aggregated statistics to admins', function () {
    $admin = User::factory()->admin()->create();
    $tutorA = User::factory()->create([
        'name' => 'Tutor Alpha',
        'email' => 'alpha@example.com',
    ]);
    $tutorB = User::factory()->create([
        'name' => 'Tutor Beta',
        'email' => 'beta@example.com',
    ]);
    $semester = Semester::factory()->create([
        'semester' => 'WS 2025/2026',
        'start' => '2025-10-01',
        'end' => '2026-03-31',
    ]);
    $degree = Degree::factory()->create(['name' => 'Informatik']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);
    $baseTopics = array_fill_keys(array_keys(Attendance::topicOptions()), false);

    Attendance::factory()
        ->for($tutorA)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'date' => '2025-11-12',
            'startTime' => '10:00:00',
            'endTime' => '11:30:00',
            'programming' => true,
            'online' => true,
            'visitors' => 3,
        ]);

    Attendance::factory()
        ->for($tutorB)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'date' => '2025-11-13',
            'startTime' => '12:00:00',
            'endTime' => '13:00:00',
            'physics' => true,
            'online' => false,
            'visitors' => 2,
        ]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/statistics/index')
            ->where('week', '2025-11-10')
            ->where('stats.totals.entries', 2)
            ->where('stats.totals.visitors', 5)
            ->where('stats.totals.minutes', 150)
            ->where('stats.totals.hours', 2.5)
            ->where('stats.totals.activeTutors', 2)
            ->where('stats.totals.onlineEntries', 1)
            ->where('stats.totals.presenceEntries', 1)
            ->where('stats.weekly.current', '2025-11-10')
            ->where('stats.weekly.totalEntries', 2)
            ->where('stats.weekly.days.2.entries', 1)
            ->where('stats.weekly.days.3.entries', 1)
            ->where('stats.weekly.semesterWeeks.0.label', 'KW 14')
            ->where('stats.weekly.semesterWeeks.26.label', 'KW 40')
            ->where('stats.faculties.0.label', 'Naturwissenschaften')
            ->where('stats.degrees.0.label', 'Informatik')
            ->where('stats.topics.0.label', 'Programmierung')
            ->has('formOptions.tutors', 3));
});

it('filters statistics by tutor', function () {
    $admin = User::factory()->admin()->create();
    $tutorA = User::factory()->create();
    $tutorB = User::factory()->create();
    $semester = Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $degree = Degree::factory()->create(['name' => 'Informatik']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);
    $baseTopics = array_fill_keys(array_keys(Attendance::topicOptions()), false);

    Attendance::factory()
        ->for($tutorA)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'date' => '2025-11-12',
            'startTime' => '10:00:00',
            'endTime' => '11:00:00',
            'programming' => true,
        ]);

    Attendance::factory()
        ->for($tutorB)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'date' => '2025-11-13',
            'startTime' => '11:00:00',
            'endTime' => '12:00:00',
            'physics' => true,
        ]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', [
            'user' => $tutorA->id,
            'week' => '2025-11-10',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.user', (string) $tutorA->id)
            ->where('week', '2025-11-10')
            ->where('stats.totals.entries', 1)
            ->where('stats.totals.activeTutors', 1)
            ->where('stats.weekly.totalEntries', 1)
            ->where('stats.topics.0.label', 'Programmierung'));
});

it('filters statistics by the unspecified degree', function () {
    $admin = User::factory()->admin()->create();

    Attendance::factory()->create([
        'degree' => Attendance::DEGREE_UNSPECIFIED,
        'date' => '2026-04-12',
    ]);
    Attendance::factory()->create([
        'degree' => 'Informatik',
        'date' => '2026-04-12',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index', [
            'degree' => Attendance::DEGREE_UNSPECIFIED,
            'week' => '2026-04-06',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.degree', Attendance::DEGREE_UNSPECIFIED)
            ->where('stats.totals.entries', 1)
            ->where('stats.degrees.0.label', Attendance::DEGREE_UNSPECIFIED));
});

it('forbids non admins from opening the statistics page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.statistics.index'))
        ->assertForbidden();
});

it('allows moderators to open the statistics page', function () {
    $moderator = User::factory()->create(['isMod' => true]);

    $this->actingAs($moderator)
        ->get(route('admin.statistics.index'))
        ->assertOk();
});
