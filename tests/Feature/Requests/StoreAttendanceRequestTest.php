<?php

use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validAttendancePayload(array $overrides = []): array
{
    return array_merge([
        'semester' => 'WS 2025/2026',
        'date' => '2026-04-12',
        'startTime' => '09:00',
        'endTime' => '11:00',
        'degree' => 'Informatik',
        'faculty' => 'Naturwissenschaften',
        'topics' => ['programming'],
        'online' => false,
        'visitors' => 3,
    ], $overrides);
}

beforeEach(function () {
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
    $this->user = User::factory()->create();
});

it('rejects endTime before startTime', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'startTime' => '11:00',
        'endTime' => '09:00',
    ]));

    $response->assertSessionHasErrors('endTime');
});

it('rejects endTime equal to startTime', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'startTime' => '10:00',
        'endTime' => '10:00',
    ]));

    $response->assertSessionHasErrors('endTime');
});

it('rejects an invalid time format', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'startTime' => '9:00',
        'endTime' => '25:00',
    ]));

    $response->assertSessionHasErrors(['startTime', 'endTime']);
});

it('rejects a non-existent semester', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'semester' => 'SS 1900',
    ]));

    $response->assertSessionHasErrors('semester');
});

it('rejects a non-existent degree', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'degree' => 'Zauberei',
    ]));

    $response->assertSessionHasErrors('degree');
});

it('rejects a non-existent faculty', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'faculty' => 'Hogwarts',
    ]));

    $response->assertSessionHasErrors('faculty');
});

it('rejects an invalid topic key', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'topics' => ['invalidTopic'],
    ]));

    $response->assertSessionHasErrors('topics.0');
});

it('rejects an empty topics array', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload([
        'topics' => [],
    ]));

    $response->assertSessionHasErrors('topics');
});

it('rejects dates outside the semester and in the future', function () {
    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload([
            'date' => '2025-09-30',
        ]))
        ->assertSessionHasErrors('date');

    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload([
            'date' => now()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('date');
});

it('rejects a faculty that is not assigned to the degree', function () {
    Faculty::factory()->create(['name' => 'Falscher Fachbereich']);

    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload([
            'faculty' => 'Falscher Fachbereich',
        ]))
        ->assertSessionHasErrors('faculty');
});

it('rejects archived form options', function (string $field, string $modelClass, array $attributes, string $value) {
    $model = $modelClass::factory()->create($attributes);
    $model->delete();

    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload([
            $field => $value,
        ]))
        ->assertSessionHasErrors($field);
})->with([
    'semester' => [
        'semester',
        Semester::class,
        [
            'semester' => 'Archiviertes Semester',
            'start' => '2025-01-01',
            'end' => '2025-06-30',
        ],
        'Archiviertes Semester',
    ],
    'degree' => [
        'degree',
        Degree::class,
        ['name' => 'Archivierter Studiengang'],
        'Archivierter Studiengang',
    ],
    'faculty' => [
        'faculty',
        Faculty::class,
        ['name' => 'Archivierter Fachbereich'],
        'Archivierter Fachbereich',
    ],
]);

it('rejects duplicate time slots for the same tutor', function () {
    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload())
        ->assertRedirect();

    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload())
        ->assertSessionHasErrors('startTime');

    expect($this->user->attendances()->count())->toBe(1);
});

it('requires a positive visitor count', function () {
    $this->actingAs($this->user)
        ->post(route('attendances.store'), validAttendancePayload([
            'visitors' => 0,
        ]))
        ->assertSessionHasErrors('visitors');
});

it('accepts a valid payload', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload());

    $response->assertRedirect(route('attendances.index'));
    $this->assertDatabaseHas('attendances', [
        'user_id' => $this->user->id,
        'semester' => 'WS 2025/2026',
        'online' => false,
        'visitors' => 3,
    ]);
});
