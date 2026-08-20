<?php

use Illuminate\Support\Facades\File;

test('the application uses the icon-only favicon assets', function () {
    $response = $this->get(route('login'));

    $response
        ->assertSuccessful()
        ->assertSee('href="/favicon-icon.ico"', false)
        ->assertSee('href="/favicon-icon.svg"', false)
        ->assertSee('href="/apple-touch-icon.png"', false);

    expect(File::exists(public_path('favicon-icon.ico')))->toBeTrue()
        ->and(File::exists(public_path('favicon-icon.svg')))->toBeTrue()
        ->and(File::exists(public_path('apple-touch-icon.png')))->toBeTrue()
        ->and(File::get(public_path('favicon-icon.svg')))
        ->toContain('fill="#111827"', 'fill="#ff9d0a"')
        ->not->toContain('Helpdesk', 'Reporter');
});
