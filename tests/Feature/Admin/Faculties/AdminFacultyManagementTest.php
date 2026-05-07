<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows the faculty management page to admins', function () {
    $admin = User::factory()->admin()->create();
    Faculty::factory()->create(['name' => 'Naturwissenschaften']);

    $this->actingAs($admin)
        ->get(route('admin.faculties.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/faculties/index')
            ->has('faculties.data', 1)
            ->where('faculties.data.0.name', 'Naturwissenschaften'));
});

it('allows admins to create faculties', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.faculties.store'), [
            'name' => 'Kulturwissenschaften',
        ])
        ->assertRedirect(route('admin.faculties.index'));

    $this->assertDatabaseHas('faculties', [
        'name' => 'Kulturwissenschaften',
    ]);
});

it('updates faculty labels and keeps attendance references in sync', function () {
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
        ->put(route('admin.faculties.update', $faculty), [
            'name' => 'Ingenieurwissenschaften',
        ])
        ->assertRedirect(route('admin.faculties.index'));

    $this->assertDatabaseHas('faculties', [
        'id' => $faculty->id,
        'name' => 'Ingenieurwissenschaften',
    ]);

    $this->assertDatabaseHas('attendances', [
        'user_id' => $tutor->id,
        'faculty' => 'Ingenieurwissenschaften',
    ]);
});

it('archives and restores faculties', function () {
    $admin = User::factory()->admin()->create();
    $faculty = Faculty::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.faculties.destroy', $faculty))
        ->assertRedirect(route('admin.faculties.index'));

    $this->assertSoftDeleted('faculties', [
        'id' => $faculty->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.faculties.restore', $faculty->id))
        ->assertRedirect(route('admin.faculties.index'));

    $this->assertDatabaseHas('faculties', [
        'id' => $faculty->id,
        'deleted_at' => null,
    ]);
});

it('forbids non admins from faculty management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.faculties.index'))
        ->assertForbidden();
});
