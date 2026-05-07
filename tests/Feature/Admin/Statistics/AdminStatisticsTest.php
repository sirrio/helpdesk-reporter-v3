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
            'endTime' => '11:30:00',
            'programming' => true,
            'online' => true,
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
        ]);

    $this->actingAs($admin)
        ->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/statistics/index')
            ->where('week', '2025-11-10')
            ->where('stats.totals.entries', 2)
            ->where('stats.totals.minutes', 150)
            ->where('stats.totals.hours', 2.5)
            ->where('stats.totals.activeTutors', 2)
            ->where('stats.totals.onlineEntries', 1)
            ->where('stats.totals.presenceEntries', 1)
            ->where('stats.weekly.current', '2025-11-10')
            ->where('stats.weekly.totalEntries', 2)
            ->where('stats.weekly.days.2.entries', 1)
            ->where('stats.weekly.days.3.entries', 1)
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

it('forbids non admins from opening the statistics page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.statistics.index'))
        ->assertForbidden();
});
