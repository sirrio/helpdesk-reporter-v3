<?php

use App\Models\Attendance;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exports all filtered attendance records with the established columns', function () {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create(['name' => '=Tutor Alpha']);
    $otherTutor = User::factory()->create();
    Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $baseTopics = array_fill_keys(array_keys(Attendance::topicOptions()), false);

    Attendance::factory()->for($tutor)->create([
        ...$baseTopics,
        'semester' => 'WS 2025/2026',
        'date' => '2025-11-12',
        'startTime' => '10:00:00',
        'endTime' => '11:30:00',
        'degree' => 'Informatik',
        'faculty' => 'Naturwissenschaften',
        'programming' => true,
        'online' => true,
        'visitors' => 3,
    ]);
    Attendance::factory()->for($otherTutor)->create([
        'semester' => 'SS 2026',
    ]);

    $this->travelTo('2026-08-20 14:15:16');

    $response = $this->actingAs($admin)->get(route('admin.statistics.csv', [
        'semester' => 'WS 2025/2026',
        'user' => $tutor->id,
    ]));

    $response
        ->assertOk()
        ->assertDownload('tutorienbesuche_20260820_141516.csv')
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $lines = preg_split('/\r\n|\n|\r/', $response->streamedContent());

    expect($lines)->toHaveCount(4)
        ->and($lines[0])->toBe("\xEF\xBB\xBFsep=,")
        ->and(str_getcsv($lines[1], ',', '"', ''))->toBe([
            'Semester',
            'Wochentag',
            'Datum',
            'von',
            'bis',
            'Studiengang',
            'Fachbereich',
            ...array_values(Attendance::topicOptions()),
            'Tutor',
            'Online',
            'Besucher',
        ])
        ->and(str_getcsv($lines[2], ',', '"', ''))->toBe([
            'WS 2025/2026',
            'Mittwoch',
            '2025-11-12',
            '10:00:00',
            '11:30:00',
            'Informatik',
            'Naturwissenschaften',
            '',
            '',
            '',
            '',
            'x',
            '',
            '',
            '',
            "'=Tutor Alpha",
            'x',
            '3',
        ]);
});

it('does not expose the CSV export to tutors', function () {
    $tutor = User::factory()->create();

    $this->actingAs($tutor)
        ->get(route('admin.statistics.csv'))
        ->assertForbidden();
});
