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

it('forbids non admins from opening the admin attendance page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.attendances.index'))
        ->assertForbidden();
});
