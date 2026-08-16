<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves attendance relations across user and reference tables', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->create([
        'semester' => 'WS 2025/2026',
    ]);
    $degree = Degree::factory()->create([
        'name' => 'Informatik',
    ]);
    $faculty = Faculty::factory()->create([
        'name' => 'Naturwissenschaften',
    ]);

    $attendance = Attendance::factory()
        ->for($user)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create();

    expect($attendance->user->is($user))->toBeTrue();
    expect($attendance->semesterEntry->is($semester))->toBeTrue();
    expect($attendance->degreeEntry->is($degree))->toBeTrue();
    expect($attendance->facultyEntry->is($faculty))->toBeTrue();
    expect($user->attendances)->toHaveCount(1);
    expect($semester->attendances)->toHaveCount(1);
    expect($degree->attendances)->toHaveCount(1);
    expect($faculty->attendances)->toHaveCount(1);
});

it('does not resolve the synthetic unspecified degree to a real degree record', function () {
    Degree::factory()->create([
        'name' => Attendance::DEGREE_UNSPECIFIED,
    ]);
    $attendance = Attendance::factory()->create([
        'degree' => Attendance::DEGREE_UNSPECIFIED,
    ]);

    expect($attendance->degreeEntry)->toBeNull();
    expect(
        Attendance::query()
            ->with('degreeEntry')
            ->findOrFail($attendance->id)
            ->degreeEntry,
    )->toBeNull();
});
