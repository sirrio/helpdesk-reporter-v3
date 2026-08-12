<?php

use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('shows the statistics page with export button', function () {
    $page = visit('/admin/statistics');

    $page->assertSee('Statistik')
        ->assertSee('Als PDF exportieren')
        ->assertNoJavaScriptErrors();
});

it('renders a dedicated compact print overview', function () {
    $page = visit('/admin/statistics');

    $page->assertPresent('[data-testid="statistics-print-layout"]')
        ->assertPresent('[data-testid="statistics-print-totals"]')
        ->assertPresent('[data-testid="statistics-print-current-week"]')
        ->assertPresent('[data-testid="statistics-print-breakdowns"]')
        ->assertNoJavaScriptErrors();
});

it('clears a single applied statistics filter', function () {
    $semester = Semester::factory()->create(['semester' => 'WS 2025/2026']);
    $page = visit('/admin/statistics?semester='.urlencode($semester->semester));

    $page->assertQueryStringHas('semester', $semester->semester)
        ->click('button:has-text("Filter")')
        ->click('button#statistics-semester-filter')
        ->click('[role="option"]:has-text("Alle Semester")')
        ->click('button:has-text("Filter anwenden")')
        ->assertQueryStringMissing('semester')
        ->assertNoJavaScriptErrors();
});
