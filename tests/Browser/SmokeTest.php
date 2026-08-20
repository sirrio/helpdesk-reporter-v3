<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the login page without JavaScript errors', function () {
    $page = visit('/login');

    $page->assertNoJavaScriptErrors();
});

it('loads authenticated pages without JavaScript errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $pages = visit(['/attendances']);

    $pages->assertNoJavaScriptErrors();
});

it('loads admin pages without JavaScript errors', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $pages = visit([
        '/admin/attendances',
        '/admin/users',
        '/admin/statistics',
    ]);

    $pages->assertNoJavaScriptErrors();
});
