<?php

use App\Models\SystemSetting;
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

it('updates the anonymization period and shows automation health', function () {
    $page = visit('/admin/users');

    $page->assertSee('Automatisierung')
        ->click('button:has-text("Automatisierung")')
        ->assertSee('Automatische Anonymisierung')
        ->assertSee('Noch kein Signal')
        ->fill('input#anonymization-months', '3')
        ->click('button:has-text("Frist speichern")')
        ->click('button:has-text("Jetzt testen")')
        ->assertSee('Test läuft')
        ->assertNoJavaScriptErrors();

    expect(SystemSetting::current()->user_anonymization_months)->toBe(3);
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

it('deactivates and reactivates a user from the admin page', function () {
    $user = User::factory()->create(['name' => 'Tutor Status']);

    $page = visit('/admin/users');

    $page->click('article:has-text("Tutor Status") button:has-text("Deaktivieren")')
        ->assertSee('Benutzer deaktivieren?')
        ->click('[role="dialog"] button:has-text("Deaktivieren")')
        ->assertSee('Reaktivieren')
        ->assertNoJavaScriptErrors();

    $this->assertSoftDeleted($user);

    $page->click('article:has-text("Tutor Status") button:has-text("Reaktivieren")')
        ->assertSee('Aktiv')
        ->assertNoJavaScriptErrors();

    $this->assertNotSoftDeleted($user);
});

it('does not show admin content to non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/admin/users');

    $response->assertForbidden();
});
