<?php

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
