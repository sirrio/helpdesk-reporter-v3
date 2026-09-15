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

it('updates faculty labels and keeps attendance references in sync', function (bool $archivedAttendance) {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create();
    $semester = Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $degree = Degree::factory()->create(['name' => 'Informatik']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);

    $attendance = Attendance::factory()
        ->for($tutor)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create();

    if ($archivedAttendance) {
        $attendance->delete();
    }

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

    expect($attendance->refresh()->trashed())->toBe($archivedAttendance);
    if ($archivedAttendance) {
        $attendance->restore();
    }

    expect($faculty->refresh()->attendances()->sole()->id)->toBe($attendance->id);
})->with([false, true]);

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

it('filters faculties by their archive status', function (string $status, int $count) {
    $this->actingAs(User::factory()->admin()->create());
    $active = Faculty::factory()->create();
    $archived = Faculty::factory()->create(['deleted_at' => now()]);

    $this->get(route('admin.faculties.index', ['status' => $status]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', $status)
            ->has('faculties.data', $count)
            ->when($status !== 'all', fn (Assert $page) => $page
                ->where('faculties.data.0.id', $status === 'active' ? $active->id : $archived->id)));
})->with(['active' => ['active', 1], 'archived' => ['archived', 1], 'all' => ['all', 2]]);

it('preserves the faculties list context after changes', function (string $action, string $method) {
    $this->actingAs(User::factory()->admin()->create());
    $record = Faculty::factory()->create(['deleted_at' => $action === 'restore' ? now() : null]);
    $context = ['status' => $action === 'restore' ? 'archived' : 'active', 'page' => 2];
    $parameters = $action === 'store' ? $context : [$record->id, ...$context];

    $this->{$method}(route('admin.faculties.'.$action, $parameters), ['name' => 'Neuer Fachbereich'])
        ->assertRedirect(route('admin.faculties.index', $context));
})->with(['create' => ['store', 'post'], 'update' => ['update', 'put'], 'archive' => ['destroy', 'delete'], 'restore' => ['restore', 'patch']]);

it('returns to the last existing faculties page', function () {
    $this->actingAs(User::factory()->admin()->create());
    Faculty::factory()->create();

    $this->get(route('admin.faculties.index', ['status' => 'active', 'page' => 2]))
        ->assertRedirect(route('admin.faculties.index', ['status' => 'active', 'page' => 1]));
});

it('rejects invalid faculties context before mutations', function (string $action, string $method) {
    $this->actingAs(User::factory()->admin()->create());
    $record = Faculty::factory()->create(['deleted_at' => $action === 'restore' ? now() : null]);
    $original = $record->fresh()->getAttributes();
    $context = ['status' => 'invalid', 'page' => 0];
    $parameters = $action === 'store' ? $context : [$record->id, ...$context];

    $this->{$method}(route('admin.faculties.'.$action, $parameters), ['name' => 'Neuer Fachbereich'])
        ->assertSessionHasErrors(['status', 'page']);

    expect(Faculty::withTrashed()->count())->toBe(1)
        ->and($record->fresh()->getAttributes())->toBe($original);
})->with(['create' => ['store', 'post'], 'update' => ['update', 'put'], 'archive' => ['destroy', 'delete'], 'restore' => ['restore', 'patch']]);
