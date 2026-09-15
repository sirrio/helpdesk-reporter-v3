<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows the semester management page to admins', function () {
    $admin = User::factory()->admin()->create();
    Semester::factory()->create([
        'semester' => 'WS 2025/2026',
        'start' => '2025-10-01',
        'end' => '2026-03-31',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.semesters.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/semesters/index')
            ->has('semesters.data', 1)
            ->where('semesters.data.0.semester', 'WS 2025/2026'));
});

it('allows admins to create semesters', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.semesters.store'), [
        'semester' => 'SS 2026',
        'start' => '2026-04-01',
        'end' => '2026-09-30',
    ]);

    $response->assertRedirect(route('admin.semesters.index'));

    $this->assertDatabaseHas('semesters', [
        'semester' => 'SS 2026',
        'start' => '2026-04-01 00:00:00',
        'end' => '2026-09-30 00:00:00',
    ]);
});

it('updates semester labels and keeps attendance references in sync', function () {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create();
    $semester = Semester::factory()->create([
        'semester' => 'WS 2025/2026',
        'start' => '2025-10-01',
        'end' => '2026-03-31',
    ]);
    $degree = Degree::factory()->create(['name' => 'Informatik']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);

    Attendance::factory()
        ->for($tutor)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create();

    $response = $this->actingAs($admin)->put(route('admin.semesters.update', $semester), [
        'semester' => 'WS 2026/2027',
        'start' => '2026-10-01',
        'end' => '2027-03-31',
    ]);

    $response->assertRedirect(route('admin.semesters.index'));

    $this->assertDatabaseHas('semesters', [
        'id' => $semester->id,
        'semester' => 'WS 2026/2027',
    ]);

    $this->assertDatabaseHas('attendances', [
        'user_id' => $tutor->id,
        'semester' => 'WS 2026/2027',
    ]);
});

it('archives and restores semesters', function () {
    $admin = User::factory()->admin()->create();
    $semester = Semester::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.semesters.destroy', $semester))
        ->assertRedirect(route('admin.semesters.index'));

    $this->assertSoftDeleted('semesters', [
        'id' => $semester->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.semesters.restore', $semester->id))
        ->assertRedirect(route('admin.semesters.index'));

    $this->assertDatabaseHas('semesters', [
        'id' => $semester->id,
        'deleted_at' => null,
    ]);
});

it('forbids non admins from semester management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.semesters.index'))
        ->assertForbidden();
});

it('filters semesters by their archive status', function (string $status, int $count) {
    $this->actingAs(User::factory()->admin()->create());
    $active = Semester::factory()->create();
    $archived = Semester::factory()->create(['deleted_at' => now()]);

    $this->get(route('admin.semesters.index', ['status' => $status]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', $status)
            ->has('semesters.data', $count)
            ->when($status !== 'all', fn (Assert $page) => $page
                ->where('semesters.data.0.id', $status === 'active' ? $active->id : $archived->id)));
})->with(['active' => ['active', 1], 'archived' => ['archived', 1], 'all' => ['all', 2]]);

it('preserves the semesters list context after changes', function (string $action, string $method) {
    $this->actingAs(User::factory()->admin()->create());
    $record = Semester::factory()->create(['deleted_at' => $action === 'restore' ? now() : null]);
    $context = ['status' => $action === 'restore' ? 'archived' : 'active', 'page' => 2];
    $parameters = $action === 'store' ? $context : [$record->id, ...$context];

    $this->{$method}(route('admin.semesters.'.$action, $parameters), ['semester' => 'SS 2030', 'start' => '2030-04-01', 'end' => '2030-09-30'])
        ->assertRedirect(route('admin.semesters.index', $context));
})->with(['create' => ['store', 'post'], 'update' => ['update', 'put'], 'archive' => ['destroy', 'delete'], 'restore' => ['restore', 'patch']]);

it('returns to the last existing semesters page', function () {
    $this->actingAs(User::factory()->admin()->create());
    Semester::factory()->create();

    $this->get(route('admin.semesters.index', ['status' => 'active', 'page' => 2]))
        ->assertRedirect(route('admin.semesters.index', ['status' => 'active', 'page' => 1]));
});

it('rejects invalid semesters context before mutations', function (string $action, string $method) {
    $this->actingAs(User::factory()->admin()->create());
    $record = Semester::factory()->create(['deleted_at' => $action === 'restore' ? now() : null]);
    $original = $record->fresh()->getAttributes();
    $context = ['status' => 'invalid', 'page' => 0];
    $parameters = $action === 'store' ? $context : [$record->id, ...$context];

    $this->{$method}(route('admin.semesters.'.$action, $parameters), ['semester' => 'SS 2030', 'start' => '2030-04-01', 'end' => '2030-09-30'])
        ->assertSessionHasErrors(['status', 'page']);

    expect(Semester::withTrashed()->count())->toBe(1)
        ->and($record->fresh()->getAttributes())->toBe($original);
})->with(['create' => ['store', 'post'], 'update' => ['update', 'put'], 'archive' => ['destroy', 'delete'], 'restore' => ['restore', 'patch']]);
