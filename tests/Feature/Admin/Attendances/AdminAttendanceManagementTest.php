<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows all attendances to admins and supports tutor filtering', function () {
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
            'physics' => true,
            'online' => false,
        ]);

    $response = $this->actingAs($admin)->get(route('admin.attendances.index', [
        'user' => $tutorA->id,
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/attendances/index')
            ->has('attendances.data', 1)
            ->where('attendances.data.0.tutor.name', 'Tutor Alpha')
            ->where('attendances.data.0.tutor.email', 'alpha@example.com')
            ->where('filters.user', (string) $tutorA->id)
            ->has('formOptions.tutors'));
});

it('allows moderators to open the admin attendance page and update entries', function () {
    $moderator = User::factory()->create(['isMod' => true]);
    $tutor = User::factory()->create();
    $semester = Semester::factory()->create([
        'semester' => 'WS 2025/2026',
        'start' => '2025-10-01',
        'end' => '2026-04-30',
    ]);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);
    $degree = Degree::factory()->create([
        'name' => 'Informatik',
        'faculty_id' => $faculty->id,
    ]);
    $attendance = Attendance::factory()
        ->for($tutor)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create(['date' => '2026-04-12']);

    $this->actingAs($moderator)
        ->get(route('admin.attendances.index'))
        ->assertOk();

    $this->actingAs($moderator)
        ->put(route('attendances.update', $attendance), [
            'semester' => $semester->semester,
            'date' => '2026-04-12',
            'startTime' => '09:00',
            'endTime' => '10:00',
            'degree' => $degree->name,
            'faculty' => $faculty->name,
            'topics' => ['programming'],
            'online' => true,
            'visitors' => 2,
        ])
        ->assertRedirect();

    expect($attendance->refresh()->visitors)->toBe(2);
});

it('filters admin attendances by the unspecified degree', function () {
    $admin = User::factory()->admin()->create();

    Attendance::factory()->create([
        'degree' => Attendance::DEGREE_UNSPECIFIED,
    ]);
    Attendance::factory()->create(['degree' => 'Informatik']);

    $this->actingAs($admin)
        ->get(route('admin.attendances.index', [
            'degree' => Attendance::DEGREE_UNSPECIFIED,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.degree', Attendance::DEGREE_UNSPECIFIED)
            ->has('attendances.data', 1)
            ->where('attendances.data.0.degree', Attendance::DEGREE_UNSPECIFIED));
});

it('forbids tutors from opening the admin attendance page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.attendances.index'))
        ->assertForbidden();
});

it('allows administrators to delete attendance entries', function () {
    $attendance = Attendance::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('attendances.destroy', $attendance))
        ->assertRedirect();

    $this->assertSoftDeleted($attendance);
});
