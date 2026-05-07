<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows the degree management page to admins', function () {
    $admin = User::factory()->admin()->create();
    Degree::factory()->create(['name' => 'Informatik']);

    $this->actingAs($admin)
        ->get(route('admin.degrees.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/degrees/index')
            ->has('degrees.data', 1)
            ->where('degrees.data.0.name', 'Informatik'));
});

it('allows admins to create degrees', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.degrees.store'), [
            'name' => 'Data Science',
        ])
        ->assertRedirect(route('admin.degrees.index'));

    $this->assertDatabaseHas('degrees', [
        'name' => 'Data Science',
    ]);
});

it('updates degree labels and keeps attendance references in sync', function () {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create();
    $semester = Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $degree = Degree::factory()->create(['name' => 'Informatik']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);

    Attendance::factory()
        ->for($tutor)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create();

    $this->actingAs($admin)
        ->put(route('admin.degrees.update', $degree), [
            'name' => 'Wirtschaftsinformatik',
        ])
        ->assertRedirect(route('admin.degrees.index'));

    $this->assertDatabaseHas('degrees', [
        'id' => $degree->id,
        'name' => 'Wirtschaftsinformatik',
    ]);

    $this->assertDatabaseHas('attendances', [
        'user_id' => $tutor->id,
        'degree' => 'Wirtschaftsinformatik',
    ]);
});

it('archives and restores degrees', function () {
    $admin = User::factory()->admin()->create();
    $degree = Degree::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.degrees.destroy', $degree))
        ->assertRedirect(route('admin.degrees.index'));

    $this->assertSoftDeleted('degrees', [
        'id' => $degree->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.degrees.restore', $degree->id))
        ->assertRedirect(route('admin.degrees.index'));

    $this->assertDatabaseHas('degrees', [
        'id' => $degree->id,
        'deleted_at' => null,
    ]);
});

it('forbids non admins from degree management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.degrees.index'))
        ->assertForbidden();
});
