<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows only the authenticated tutors attendances and supports filtering', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $semester = Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $otherSemester = Semester::factory()->create(['semester' => 'SS 2026']);
    $degree = Degree::factory()->create(['name' => 'Informatik']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);
    $baseTopics = array_fill_keys(array_keys(Attendance::topicOptions()), false);

    Attendance::factory()
        ->for($user)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'programming' => true,
            'online' => true,
        ]);

    Attendance::factory()
        ->for($user)
        ->forSemester($otherSemester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'physics' => true,
            'online' => false,
        ]);

    Attendance::factory()
        ->for($otherUser)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            ...$baseTopics,
            'programming' => true,
            'online' => true,
        ]);

    $response = $this->actingAs($user)->get(route('attendances.index', [
        'topic' => 'programming',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendances/index')
            ->missing('stats')
            ->has('attendances.data', 1)
            ->where('attendances.data.0.semester', 'WS 2025/2026')
            ->where('attendances.data.0.online', true)
            ->where('filters.topic', 'programming'));
});

it('stores a new attendance entry for the authenticated tutor', function () {
    $user = User::factory()->create();
    Semester::factory()->create([
        'semester' => 'WS 2025/2026',
        'start' => '2025-10-01',
        'end' => '2026-04-30',
    ]);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);
    Degree::factory()->create([
        'name' => 'Informatik',
        'faculty_id' => $faculty->id,
    ]);

    $response = $this->actingAs($user)->post(route('attendances.store'), [
        'semester' => 'WS 2025/2026',
        'date' => '2026-04-12',
        'startTime' => '09:00',
        'endTime' => '11:00',
        'degree' => 'Informatik',
        'faculty' => 'Naturwissenschaften',
        'topics' => ['programming', 'mathBasic'],
        'online' => true,
        'visitors' => 4,
    ]);

    $response->assertRedirect(route('attendances.index'));

    $this->assertDatabaseHas('attendances', [
        'user_id' => $user->id,
        'semester' => 'WS 2025/2026',
        'date' => '2026-04-12 00:00:00',
        'startTime' => '09:00:00',
        'endTime' => '11:00:00',
        'degree' => 'Informatik',
        'faculty' => 'Naturwissenschaften',
        'programming' => true,
        'mathBasic' => true,
        'physics' => false,
        'online' => true,
        'visitors' => 4,
    ]);
});

it('allows a tutor to update their own attendance entry', function () {
    $user = User::factory()->create();
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
        ->for($user)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            'date' => '2026-04-12',
            'startTime' => '09:00:00',
            'endTime' => '10:00:00',
        ]);

    $this->actingAs($user)
        ->put(route('attendances.update', $attendance), [
            'semester' => $semester->semester,
            'date' => '2026-04-12',
            'startTime' => '10:00',
            'endTime' => '11:30',
            'degree' => $degree->name,
            'faculty' => $faculty->name,
            'topics' => ['mathHigh'],
            'online' => false,
            'visitors' => 5,
        ])
        ->assertRedirect();

    $attendance->refresh();

    expect($attendance)
        ->startTime->toBe('10:00:00')
        ->endTime->toBe('11:30:00')
        ->visitors->toBe(5)
        ->mathHigh->toBeTrue();
});

it('allows unchanged archived reference values when updating an attendance', function () {
    $user = User::factory()->create();
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
        ->for($user)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            'date' => '2026-04-12',
            'startTime' => '09:00:00',
            'endTime' => '10:00:00',
        ]);

    $semester->delete();
    $degree->delete();
    $faculty->delete();

    $this->actingAs($user)
        ->put(route('attendances.update', $attendance), [
            'semester' => $semester->semester,
            'date' => '2026-04-12',
            'startTime' => '09:00',
            'endTime' => '10:00',
            'degree' => $degree->name,
            'faculty' => $faculty->name,
            'topics' => ['programming'],
            'online' => false,
            'visitors' => 7,
        ])
        ->assertRedirect();

    expect($attendance->refresh()->visitors)->toBe(7);
});

it('forbids tutors from updating another tutors attendance entry', function () {
    $attendance = Attendance::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('attendances.update', $attendance), [])
        ->assertForbidden();
});
