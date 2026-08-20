<?php

use App\Models\Attendance;
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

it('organizes admin attendance cards on desktop and mobile', function () {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create([
        'name' => 'Tutor Beta',
        'email' => 'tutor.beta@example.com',
    ]);

    Attendance::factory()->for($tutor)->create([
        'semester' => 'SS 2025',
        'degree' => 'Informatik',
        'faculty' => 'Informatik',
        'mathBasic' => true,
        'mathFractions' => false,
        'mathLow' => false,
        'mathHigh' => false,
        'programming' => true,
        'physics' => false,
        'chemistry' => false,
        'organization' => false,
        'visitors' => 1,
    ]);

    $this->actingAs($admin);

    foreach ([[1440, 900], [390, 844]] as [$width, $height]) {
        $page = visit('/admin/attendances')->resize($width, $height);

        $page->assertPresent('[data-testid="admin-attendance-card"]')
            ->assertPresent('[data-testid="admin-attendance-card-actions"]')
            ->assertPresent('[data-testid="admin-attendance-card-details"]')
            ->assertPresent('[data-testid="admin-attendance-card-topics"]')
            ->assertSee('Tutor:in')
            ->assertSee('Semester')
            ->assertSee('Studiengang')
            ->assertSee('Fachbereich')
            ->assertSee('Themen')
            ->assertNoJavaScriptErrors();
    }
});
