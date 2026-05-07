<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('shows the admin users page', function () {
    $page = visit('/admin/users');

    $page->assertSee('Neuer Benutzer')
        ->assertNoJavaScriptErrors();
});

it('opens the create user dialog', function () {
    $page = visit('/admin/users');

    $page->click('button:has-text("Neuer Benutzer")')
        ->assertSee('Benutzer speichern')
        ->assertNoJavaScriptErrors();
});

it('creates a new user', function () {
    $page = visit('/admin/users');

    $page->click('button:has-text("Neuer Benutzer")')
        ->fill('input#create-name', 'Max Mustermann')
        ->fill('input#create-email', 'max@example.com')
        ->fill('input#create-password', 'password123')
        ->click('button:has-text("Benutzer speichern")')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'Max Mustermann',
        'email' => 'max@example.com',
    ]);
});

it('shows validation errors when creating user with duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $page = visit('/admin/users');

    $page->click('button:has-text("Neuer Benutzer")')
        ->fill('input#create-name', 'Test User')
        ->fill('input#create-email', 'existing@example.com')
        ->fill('input#create-password', 'password123')
        ->click('button:has-text("Benutzer speichern")')
        ->assertSee('Benutzer speichern')
        ->assertNoJavaScriptErrors();
});

it('does not show admin content to non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/admin/users');

    $response->assertForbidden();
});
