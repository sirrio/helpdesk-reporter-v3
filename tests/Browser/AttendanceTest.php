<?php

use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Semester::factory()->create(['semester' => 'WS 2025/2026']);
    Degree::factory()->create(['name' => 'Informatik']);
    Faculty::factory()->create(['name' => 'Naturwissenschaften']);

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
