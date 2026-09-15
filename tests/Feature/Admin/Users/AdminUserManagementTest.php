<?php

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    $managedUser = User::query()->where('email', 'gamma@example.com')->firstOrFail();

    expect($managedUser->approved_at)
        ->not->toBeNull()
        ->and($managedUser->must_change_password)->toBeTrue();
});

it('shows pending registrations and allows admins to approve them', function () {
    $admin = User::factory()->admin()->create();
    $pendingUser = User::factory()->pendingApproval()->create([
        'name' => 'Pending Tutor',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'pending')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Pending Tutor')
            ->where('users.data.0.approvedAt', null));

    $this->actingAs($admin)
        ->patch(route('admin.users.approve', $pendingUser))
        ->assertRedirect(route('admin.users.index'));

    expect($pendingUser->refresh()->approved_at)->not->toBeNull();
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

it('turns an admin password reset into a required password change and revokes access state', function () {
    config()->set('session.driver', 'database');

    $admin = User::factory()->admin()->create();
    $user = User::factory()->withTwoFactor()->create([
        'remember_token' => 'remember-token',
    ]);

    DB::table('sessions')->insert([
        'id' => 'reset-user-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'temporary-password',
        'isMod' => false,
        'isAdmin' => false,
    ])->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->must_change_password)
        ->toBeTrue()
        ->and($user->remember_token)->toBeNull()
        ->and($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
    $this->assertDatabaseMissing('sessions', ['id' => 'reset-user-session']);
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

it('shows active and deactivated users and filters by status', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Active Tutor']);
    $deactivatedUser = User::factory()->create(['name' => 'Former Tutor']);
    $deactivatedUser->delete();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['status' => 'deactivated']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'deactivated')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Former Tutor')
            ->where('users.data.0.deletedAt', fn (mixed $value): bool => is_string($value)));
});

it('filters anonymized users separately', function () {
    $admin = User::factory()->admin()->create();
    $anonymizedUser = User::factory()->create();
    $anonymizedUser->delete();
    $anonymizedUser->anonymize();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['status' => 'anonymized']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'anonymized')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Anonymisierter Account')
            ->where('users.data.0.anonymizedAt', fn (mixed $value): bool => is_string($value)));
});

it('allows admins to deactivate users while preserving their attendances', function () {
    config()->set('session.driver', 'database');

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['remember_token' => 'remember-token']);
    $attendance = Attendance::factory()->for($user)->create();

    DB::table('sessions')->insert([
        'id' => 'managed-user-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    $this->assertSoftDeleted($user);
    $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    $this->assertDatabaseMissing('sessions', ['id' => 'managed-user-session']);

    $deactivatedUser = User::withTrashed()->findOrFail($user->id);

    expect($deactivatedUser->remember_token)
        ->not->toBe('remember-token')
        ->and($attendance->fresh()->user?->name)->toBe($user->name);
});

it('prevents admins from deactivating their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    $this->assertNotSoftDeleted($admin);
});

it('allows admins to reactivate users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $user->delete();

    $this->actingAs($admin)
        ->patch(route('admin.users.restore', $user->id))
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'deleted_at' => null,
    ]);
});

it('does not reactivate anonymized users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $user->delete();
    $user->anonymize();

    $this->actingAs($admin)
        ->patch(route('admin.users.restore', $user->id))
        ->assertStatus(409);

    $this->assertSoftDeleted($user);
});

it('prevents deactivated users from signing in', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'email' => 'former@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user));

    $this->post(route('logout'));
    $this->post(route('login.store'), [
        'email' => 'former@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

it('forbids non admins from opening user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('forbids non admins from changing user activation status', function () {
    $user = User::factory()->create();
    $managedUser = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('admin.users.destroy', $managedUser))
        ->assertForbidden();
});

it('keeps the applied user filters after management actions', function (string $action, string $method) {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $context = ['search' => 'Tutor', 'role' => 'tutor', 'status' => 'active', 'page' => 2];
    $parameters = $context;
    if (in_array($action, ['update', 'destroy', 'restore', 'approve'], true)) {
        $parameters['user'] = $user;
    }
    if ($action === 'restore') {
        $user->delete();
    }
    $payload = match ($action) {
        'store', 'update' => ['name' => 'Tutor Changed', 'email' => 'changed@example.com', 'password' => 'secretpass', 'isMod' => false, 'isAdmin' => false],
        'automation.update' => ['anonymizationMonths' => 6],
        default => [],
    };

    $this->actingAs($admin)->{$method}(route('admin.users.'.$action, $parameters), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.index', $context));
})->with([
    'create' => ['store', 'post'],
    'edit' => ['update', 'put'],
    'deactivate' => ['destroy', 'delete'],
    'restore' => ['restore', 'patch'],
    'approve' => ['approve', 'patch'],
    'automation settings' => ['automation.update', 'patch'],
    'automation check' => ['automation.check', 'post'],
]);

it('returns to the last available filtered user page after deactivation', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(15)->create(['name' => 'Matching Tutor']);
    $user = User::factory()->create(['name' => 'Matching Tutor Last']);
    $context = ['search' => 'Matching', 'role' => 'tutor', 'status' => 'active', 'page' => 2];

    $this->actingAs($admin)->delete(route('admin.users.destroy', ['user' => $user, ...$context]))
        ->assertRedirect(route('admin.users.index', $context));
    $this->get(route('admin.users.index', $context))
        ->assertRedirect(route('admin.users.index', [...$context, 'page' => 1]));
    $this->get(route('admin.users.index', [...$context, 'page' => 1]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Matching')
            ->where('filters.role', 'tutor')
            ->where('filters.status', 'active')
            ->has('users.data', 15));
});

it('rejects invalid user list context before changing a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', ['user' => $user, 'page' => -1]))
        ->assertSessionHasErrors('page');

    $this->assertNotSoftDeleted($user);
});
