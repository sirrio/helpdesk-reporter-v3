<?php

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('keeps the selected status synchronized with the degree list', function (int $width, int $height) {
    Degree::factory()->create(['name' => 'Aktiver Teststudiengang']);
    Degree::factory()->create(['name' => 'Archivierter Teststudiengang', 'deleted_at' => now()]);

    $page = visit('/admin/degrees')->resize($width, $height);
    $page->assertSee('Aktiver Teststudiengang')->assertSee('Archivierter Teststudiengang');

    foreach (['Archiviert' => 'archived', 'Aktiv' => 'active', 'Alle' => ''] as $label => $status) {
        $page->click('[aria-label="Studiengänge filtern"]')
            ->click('[role="option"]:has-text("'.$label.'")')
            ->assertSeeIn('[aria-label="Studiengänge filtern"]', $label);

        if ($status === 'archived') {
            $page->assertSee('Archivierter Teststudiengang')->assertDontSee('Aktiver Teststudiengang');
        } elseif ($status === 'active') {
            $page->assertSee('Aktiver Teststudiengang')->assertDontSee('Archivierter Teststudiengang');
        } else {
            $page->assertSee('Aktiver Teststudiengang')->assertSee('Archivierter Teststudiengang');
        }
    }

    $page->assertNoJavaScriptErrors();
})->with(['desktop' => [1440, 1000], 'mobile' => [390, 844]]);

it('preserves the page filter and scroll position when archiving', function (int $width, int $height) {
    foreach (range(1, 35) as $number) {
        fake()->unique(true);
        Degree::factory()->create(['name' => sprintf('Studiengang %02d', $number)]);
    }

    $page = visit('/admin/degrees?status=active&page=2')->resize($width, $height);
    $page->assertSee('Studiengang 16');
    $page->script('document.querySelectorAll("article")[5].scrollIntoView({block: "center"})');
    $page->click('article:has-text("Studiengang 21") button:has-text("Archivieren")')
        ->assertDontSee('Studiengang 21')
        ->assertQueryStringHas('status', 'active')
        ->assertQueryStringHas('page', '2')
        ->assertSee('Studiengang 16')
        ->assertSeeIn('[aria-label="Studiengänge filtern"]', 'Aktiv')
        ->assertScript('window.scrollY > 0', true)
        ->assertNoJavaScriptErrors();
})->with(['desktop' => [1440, 1000], 'mobile' => [390, 844]]);

it('confirms deletion of unused archived degrees and protects used degrees', function (int $width, int $height) {
    $unused = Degree::factory()->create(['name' => 'Unbenutzter Studiengang', 'deleted_at' => now()]);
    $used = Degree::factory()->create(['name' => 'Benutzter Studiengang', 'deleted_at' => now()]);
    Attendance::factory()->forDegree($used)->create();

    $page = visit('/admin/degrees?status=archived')->resize($width, $height);
    $page->assertDisabled('article:has(span:text-is("Benutzter Studiengang")) button:has-text("Löschen")')
        ->click('article:has-text("Unbenutzter Studiengang") button:has-text("Löschen")')
        ->assertSee('Studiengang endgültig löschen?')
        ->screenshot(filename: 'degree-delete-'.$width)
        ->click('[role="dialog"] button:has-text("Abbrechen")')
        ->assertSee('Unbenutzter Studiengang');

    $this->assertModelExists($unused);

    $page->click('article:has-text("Unbenutzter Studiengang") button:has-text("Löschen")')
        ->click('[role="dialog"] button:has-text("Endgültig löschen")')
        ->assertDontSee('Unbenutzter Studiengang')
        ->assertSee('Benutzter Studiengang')
        ->assertQueryStringHas('status', 'archived')
        ->assertNoJavaScriptErrors();

    $this->assertModelMissing($unused);
    $this->assertModelExists($used);
})->with(['desktop' => [1440, 1000], 'mobile' => [390, 844]]);
