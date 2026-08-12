<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    $this->actingAs($this->user);
});

it('shows the attendances index page', function () {
    $page = visit('/attendances');

    $page->assertSee('Neuer Eintrag')
        ->assertNoJavaScriptErrors();
});

it('opens the create attendance dialog', function () {
    $page = visit('/attendances');

    $page->click('button:has-text("Neuer Eintrag")')
        ->assertSee('Neuen Eintrag erstellen')
        ->assertSee('Anzahl Besucher:innen')
        ->assertNoJavaScriptErrors();
});

it('creates a new attendance entry', function () {
    $page = visit('/attendances');

    $page->click('button:has-text("Neuer Eintrag")')
        ->assertSee('Neuen Eintrag erstellen')
        ->fill('input#date', '2026-04-12')
        ->fill('input#startTime', '09:00')
        ->fill('input#endTime', '11:00')
        ->click('Programmierung')
        ->click('button:has-text("Eintrag speichern")')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('attendances', [
        'user_id' => $this->user->id,
        'semester' => 'WS 2025/2026',
    ]);
});

it('shows validation error when end time is before start time', function () {
    $page = visit('/attendances');

    $page->click('button:has-text("Neuer Eintrag")')
        ->fill('input#date', '2026-04-12')
        ->fill('input#startTime', '11:00')
        ->fill('input#endTime', '09:00')
        ->click('button:has-text("Eintrag speichern")')
        ->assertSee('Eintrag speichern')
        ->assertNoJavaScriptErrors();
});

it('opens and saves the edit dialog for an existing entry', function () {
    $semester = Semester::query()->firstOrFail();
    $degree = Degree::query()->firstOrFail();
    $faculty = Faculty::query()->firstOrFail();
    Attendance::factory()
        ->for($this->user)
        ->forSemester($semester)
        ->forDegree($degree)
        ->forFaculty($faculty)
        ->create([
            'date' => '2026-04-12',
            'startTime' => '09:00:00',
            'endTime' => '10:00:00',
            'visitors' => 1,
        ]);

    $page = visit('/attendances');

    $page->click('button:has-text("Bearbeiten")')
        ->assertSee('Eintrag bearbeiten')
        ->keys('input#edit-date', ['End', 'Backspace'])
        ->assertValue('input#edit-date', '12.04.202')
        ->typeSlowly('input#edit-date', '6')
        ->assertValue('input#edit-date', '12.04.2026')
        ->fill('input#edit-visitors', '6')
        ->click('button:has-text("Änderungen speichern")')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('attendances', [
        'user_id' => $this->user->id,
        'visitors' => 6,
    ]);
});
