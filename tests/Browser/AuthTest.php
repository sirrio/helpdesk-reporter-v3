<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated users to login', function () {
    $page = visit('/attendances');

    $page->assertPathContains('/login')
        ->assertNoJavaScriptErrors();
});

it('shows login page without JavaScript errors', function () {
    $page = visit('/login');

    $page->assertNoJavaScriptErrors();
});

it('explains the approval workflow on desktop and mobile registration', function () {
    foreach ([[1280, 900], [390, 844]] as [$width, $height]) {
        $page = visit('/register')->resize($width, $height);

        $page->assertSee('Freischaltung')
            ->assertSee('keine Anmeldung möglich')
            ->assertNoJavaScriptErrors();
    }
});

it('shows validation errors on invalid login credentials', function () {
    $page = visit('/login');

    $page->fill('input[name=email]', 'wrong@example.com')
        ->fill('input[name=password]', 'wrongpassword')
        ->click('[data-test=login-button]')
        ->assertSee('These credentials do not match our records')
        ->assertNoJavaScriptErrors();
});

it('logs in successfully and redirects to attendances', function () {
    $user = User::factory()->create([
        'email' => 'tutor@test.test',
        'password' => bcrypt('password'),
    ]);

    $page = visit('/login');

    $page->fill('input[name=email]', 'tutor@test.test')
        ->fill('input[name=password]', 'password')
        ->click('[data-test=login-button]')
        ->assertPathContains('/attendances')
        ->assertNoJavaScriptErrors();
});
