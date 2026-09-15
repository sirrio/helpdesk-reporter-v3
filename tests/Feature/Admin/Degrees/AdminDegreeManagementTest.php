<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('reserves collation-equivalent unspecified attendance labels', function (string $reservedName) {
    $admin = User::factory()->admin()->create();
    $faculty = Faculty::factory()->create();
    $degree = Degree::factory()->create([
        'name' => 'Informatik',
        'faculty_id' => $faculty->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.degrees.store'), [
            'name' => $reservedName,
            'faculty_id' => $faculty->id,
        ])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->put(route('admin.degrees.update', $degree), [
            'name' => $reservedName,
            'faculty_id' => $faculty->id,
        ])
        ->assertSessionHasErrors('name');

    expect($degree->refresh()->name)->toBe('Informatik');
})->with([
    'canonical' => Attendance::DEGREE_UNSPECIFIED,
    'lowercase' => 'keine angabe',
    'uppercase' => 'KEINE ANGABE',
    'surrounding whitespace' => '  Keine Angabe  ',
    'unicode whitespace' => "Keine\u{00A0}Angabe",
    'accent-insensitive variant' => 'Keiné Angabe',
]);

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
    $faculty = Faculty::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.degrees.store'), [
            'name' => 'Data Science',
            'faculty_id' => $faculty->id,
        ])
        ->assertRedirect(route('admin.degrees.index'));

    $this->assertDatabaseHas('degrees', [
        'name' => 'Data Science',
        'faculty_id' => $faculty->id,
    ]);
});

it('updates degree labels and keeps attendance references in sync', function () {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create();
    $semester = Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $faculty = Faculty::factory()->create(['name' => 'Naturwissenschaften']);
    $newFaculty = Faculty::factory()->create(['name' => 'Informatik']);
    $degree = Degree::factory()->create([
        'name' => 'Informatik',
        'faculty_id' => $faculty->id,
    ]);

    Attendance::factory()
        ->for($tutor)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create();

    $this->actingAs($admin)
        ->put(route('admin.degrees.update', $degree), [
            'name' => 'Wirtschaftsinformatik',
            'faculty_id' => $newFaculty->id,
        ])
        ->assertRedirect(route('admin.degrees.index'));

    $this->assertDatabaseHas('degrees', [
        'id' => $degree->id,
        'name' => 'Wirtschaftsinformatik',
        'faculty_id' => $newFaculty->id,
    ]);

    $this->assertDatabaseHas('attendances', [
        'user_id' => $tutor->id,
        'degree' => 'Wirtschaftsinformatik',
        'faculty' => 'Informatik',
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

it('filters degrees by their archive status', function (string $status, array $names) {
    Degree::factory()->create(['name' => 'Active degree']);
    Degree::factory()->create(['name' => 'Archived degree', 'deleted_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.degrees.index', ['status' => $status]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', $status)
            ->where('degrees.data', fn ($degrees) => $degrees->pluck('name')->all() === $names));
})->with([
    ['all', ['Active degree', 'Archived degree']],
    ['active', ['Active degree']],
    ['archived', ['Archived degree']],
]);

it('keeps the filter and page when archiving restoring and editing degrees', function () {
    $degree = Degree::factory()->for(Faculty::factory())->create();
    $context = ['status' => 'all', 'page' => 2];
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.degrees.update', ['degree' => $degree, ...$context]), [
        'name' => 'Updated degree',
        'faculty_id' => $degree->faculty_id,
    ])->assertRedirect(route('admin.degrees.index', $context));

    $this->delete(route('admin.degrees.destroy', ['degree' => $degree, ...$context]))
        ->assertRedirect(route('admin.degrees.index', $context));

    $this->patch(route('admin.degrees.restore', ['degree' => $degree->id, ...$context]))
        ->assertRedirect(route('admin.degrees.index', $context));
});

it('returns to the last available page when archiving empties the current page', function () {
    for ($number = 1; $number <= 30; $number++) {
        fake()->unique(true);
        Degree::factory()->create(['name' => sprintf('Degree %02d', $number)]);
    }
    $degree = Degree::factory()->create(['name' => 'ZZZ last degree']);
    $context = ['status' => 'active', 'page' => 3];
    $this->actingAs(User::factory()->admin()->create());

    $this->delete(route('admin.degrees.destroy', ['degree' => $degree, ...$context]))
        ->assertRedirect(route('admin.degrees.index', $context));

    $this->get(route('admin.degrees.index', $context))
        ->assertRedirect(route('admin.degrees.index', ['status' => 'active', 'page' => 2]));

    $this->get(route('admin.degrees.index', ['status' => 'active', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('degrees.current_page', 2)
            ->has('degrees.data', 15));
});

it('rejects invalid list context before mutating a degree', function (array $context, string $error) {
    $degree = Degree::factory()->for(Faculty::factory())->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->delete(route('admin.degrees.destroy', ['degree' => $degree, ...$context]))
        ->assertSessionHasErrors($error);

    $this->put(route('admin.degrees.update', ['degree' => $degree, ...$context]), [
        'name' => 'Changed',
        'faculty_id' => $degree->faculty_id,
    ])->assertSessionHasErrors($error);

    expect($degree->refresh()->trashed())->toBeFalse()
        ->and($degree->name)->not->toBe('Changed');
})->with([
    [['status' => 'unknown'], 'status'],
    [['page' => 0], 'page'],
]);

it('permanently deletes unused archived degrees and preserves list context', function () {
    $degree = Degree::factory()->create(['deleted_at' => now()]);
    $context = ['status' => 'archived', 'page' => 2];

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.degrees.force-destroy', ['degree' => $degree->id, ...$context]))
        ->assertRedirect(route('admin.degrees.index', $context));

    $this->assertModelMissing($degree);
});

it('prevents permanent deletion of active degrees', function () {
    $degree = Degree::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.degrees.force-destroy', $degree->id))
        ->assertSessionHasErrors('degree');

    $this->assertModelExists($degree);
    expect($degree->refresh()->trashed())->toBeFalse();
});

it('keeps archived degrees with attendance history', function (bool $archivedAttendance) {
    $degree = Degree::factory()->create();
    $attendance = Attendance::factory()->forDegree($degree)->create();
    if ($archivedAttendance) {
        $attendance->delete();
    }
    $degree->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.degrees.force-destroy', $degree->id))
        ->assertSessionHasErrors('degree');

    $this->assertModelExists($degree);
    $this->assertModelExists($attendance);
    $this->get(route('admin.degrees.index', ['status' => 'archived']))
        ->assertInertia(fn (Assert $page) => $page->where('degrees.data.0.canDelete', false));
})->with([false, true]);

it('forbids non admins from permanently deleting degrees', function () {
    $degree = Degree::factory()->create(['deleted_at' => now()]);

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.degrees.force-destroy', $degree->id))
        ->assertForbidden();

    $this->assertModelExists($degree);
});

it('preserves the degree list context when creating a degree', function () {
    $faculty = Faculty::factory()->create();
    $context = ['status' => 'active', 'page' => 2];

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.degrees.store', $context), [
            'name' => 'New degree',
            'faculty_id' => $faculty->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.degrees.index', $context));

    $this->assertDatabaseHas('degrees', ['name' => 'New degree', 'faculty_id' => $faculty->id]);
});

it('rejects invalid degree list context before creating a degree', function () {
    $faculty = Faculty::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.degrees.store', ['status' => 'invalid', 'page' => 0]), [
            'name' => 'New degree',
            'faculty_id' => $faculty->id,
        ])
        ->assertSessionHasErrors(['status', 'page']);

    $this->assertDatabaseMissing('degrees', ['name' => 'New degree']);
});

it('preserves deleted attendance history when renaming a degree before archiving it', function () {
    $degree = Degree::factory()->for(Faculty::factory())->create();
    $attendance = Attendance::factory()->forDegree($degree)->create();
    $attendance->delete();
    $newFaculty = Faculty::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.degrees.update', $degree), [
        'name' => 'Renamed degree',
        'faculty_id' => $newFaculty->id,
    ])->assertSessionHasNoErrors();

    expect($attendance->refresh()->degree)->toBe('Renamed degree')
        ->and($attendance->faculty)->toBe($newFaculty->name)
        ->and($attendance->trashed())->toBeTrue();

    $this->delete(route('admin.degrees.destroy', $degree))->assertSessionHasNoErrors();
    $this->get(route('admin.degrees.index', ['status' => 'archived']))
        ->assertInertia(fn (Assert $page) => $page->where('degrees.data.0.canDelete', false));
    $this->delete(route('admin.degrees.force-destroy', $degree->id))
        ->assertSessionHasErrors('degree');

    $this->assertModelExists($degree);
    $this->assertModelExists($attendance);
});

it('rejects a stale delete request after another admin restores the degree', function () {
    $degree = Degree::factory()->create(['deleted_at' => now()]);
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.degrees.index', ['status' => 'archived']))
        ->assertInertia(fn (Assert $page) => $page->where('degrees.data.0.canDelete', true));
    $this->patch(route('admin.degrees.restore', $degree->id))->assertSessionHasNoErrors();
    $this->delete(route('admin.degrees.force-destroy', $degree->id))
        ->assertSessionHasErrors('degree');

    $this->assertModelExists($degree);
    expect($degree->refresh()->trashed())->toBeFalse();
});
