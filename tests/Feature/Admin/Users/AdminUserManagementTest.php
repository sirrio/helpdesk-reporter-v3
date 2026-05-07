<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows the user management page to admins', function () {
    $admin = User::factory()->admin()->create();
    $tutor = User::factory()->create([
        'name' => 'Tutor Alpha',
        'email' => 'alpha@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.index', [
        'search' => 'Tutor',
    ]));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->where('filters.search', 'Tutor')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Tutor Alpha'));
});

it('allows admins to create a managed user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Tutor Gamma',
        'email' => 'gamma@example.com',
        'password' => 'secretpass',
        'isMod' => false,
        'isAdmin' => false,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'Tutor Gamma',
        'email' => 'gamma@example.com',
        'isMod' => false,
        'isAdmin' => false,
    ]);
});

it('allows admins to update a managed user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'name' => 'Tutor Delta',
        'email' => 'delta@example.com',
        'isMod' => false,
        'isAdmin' => false,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Tutor Delta Updated',
        'email' => 'delta.updated@example.com',
        'password' => '',
        'isMod' => true,
        'isAdmin' => false,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Tutor Delta Updated',
        'email' => 'delta.updated@example.com',
        'isMod' => true,
        'isAdmin' => false,
    ]);
});

it('prevents admins from removing their own admin role', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'password' => '',
        'isMod' => false,
        'isAdmin' => false,
    ]);

    $response->assertSessionHasErrors('isAdmin');
});

it('forbids non admins from opening user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});
