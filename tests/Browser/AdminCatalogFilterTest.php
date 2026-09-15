<?php

use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('keeps catalog status selections synchronized with results', function (string $model, string $path, string $field, string $label, int $width, int $height) {
    $model::factory()->create([$field => 'Aktiver Testeintrag']);
    $model::factory()->create([$field => 'Archivierter Testeintrag', 'deleted_at' => now()]);
    $selector = '[aria-label="'.$label.' filtern"]';
    $page = visit('/admin/'.$path)->resize($width, $height);
    $page->assertSee('Aktiver Testeintrag')->assertSee('Archivierter Testeintrag');

    foreach (['Archiviert', 'Aktiv', 'Alle'] as $status) {
        $page->click($selector)
            ->click('[role="option"]:has-text("'.$status.'")')
            ->assertSeeIn($selector, $status);

        if ($status === 'Archiviert') {
            $page->assertSee('Archivierter Testeintrag')->assertNotPresent('article:has-text("Aktiver Testeintrag")');
        } elseif ($status === 'Aktiv') {
            $page->assertSee('Aktiver Testeintrag')->assertNotPresent('article:has-text("Archivierter Testeintrag")');
        } else {
            $page->assertSee('Aktiver Testeintrag')->assertSee('Archivierter Testeintrag');
        }
    }

    $page->assertNoJavaScriptErrors();
})->with([
    'faculties' => [Faculty::class, 'faculties', 'name', 'Fachbereiche'],
    'semesters' => [Semester::class, 'semesters', 'semester', 'Semester'],
])->with(['desktop' => [1440, 1000], 'mobile' => [390, 844]]);

it('preserves catalog page filter and scroll after archiving', function (string $model, string $path, string $field, string $label, int $width, int $height) {
    foreach (range(1, 35) as $number) {
        fake()->unique(true);
        $attributes = [$field => sprintf('Eintrag %02d', $number)];
        if ($model === Semester::class) {
            $attributes['start'] = now()->subYears($number)->startOfYear();
        }
        $model::factory()->create($attributes);
    }

    $page = visit('/admin/'.$path.'?status=active&page=2')->resize($width, $height);
    $page->assertSee('Eintrag 16');
    $page->script('document.querySelectorAll("article")[5].scrollIntoView({block: "center"})');
    $page->assertScript('window.scrollY > 0 && window.history.state?.documentScrollPosition?.top > 0', true);
    $page->click('article:has-text("Eintrag 21") button:has-text("Archivieren")')
        ->assertDontSee('Eintrag 21')
        ->assertQueryStringHas('status', 'active')
        ->assertQueryStringHas('page', '2')
        ->assertSee('Eintrag 16')
        ->assertSeeIn('[aria-label="'.$label.' filtern"]', 'Aktiv')
        ->assertScript('window.scrollY > 0', true)
        ->assertNoJavaScriptErrors();

    if ($model === Semester::class) {
        $page->assertNotPresent('a:text-is("Eintrag 21")');
    }
})->with([
    'faculties' => [Faculty::class, 'faculties', 'name', 'Fachbereiche'],
    'semesters' => [Semester::class, 'semesters', 'semester', 'Semester'],
])->with(['desktop' => [1440, 1000], 'mobile' => [390, 844]]);
