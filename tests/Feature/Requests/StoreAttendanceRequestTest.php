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
    ], $overrides);
}

beforeEach(function () {
    Semester::factory()->create(['semester' => 'WS 2025/2026']);
    Degree::factory()->create(['name' => 'Informatik']);
    Faculty::factory()->create(['name' => 'Naturwissenschaften']);
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

it('accepts a valid payload', function () {
    $response = $this->actingAs($this->user)->post(route('attendances.store'), validAttendancePayload());

    $response->assertRedirect(route('attendances.index'));
    $this->assertDatabaseHas('attendances', [
        'user_id' => $this->user->id,
        'semester' => 'WS 2025/2026',
        'online' => false,
    ]);
});
