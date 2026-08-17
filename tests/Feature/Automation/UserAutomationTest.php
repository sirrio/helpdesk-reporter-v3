<?php

use App\Jobs\RecordQueueHeartbeat;
use App\Models\Attendance;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('uses a safe default for an incomplete legacy settings row', function () {
    $settings = new SystemSetting;
    $settings->forceFill(['user_anonymization_months' => null]);

    expect($settings->anonymizationMonths())->toBe(12);
});

it('allows admins to configure the anonymization period', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.automation.update'), [
            'anonymizationMonths' => 3,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect(SystemSetting::current()->user_anonymization_months)->toBe(3);
});

it('validates the anonymization period and forbids non admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.automation.update'), [
            'anonymizationMonths' => 0,
        ])
        ->assertSessionHasErrors('anonymizationMonths');

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.users.automation.update'), [
            'anonymizationMonths' => 3,
        ])
        ->assertForbidden();
});

it('reports scheduler and queue heartbeat health to admins', function () {
    $admin = User::factory()->admin()->create();
    SystemSetting::current()->forceFill([
        'scheduler_heartbeat_at' => now()->subMinute(),
        'queue_heartbeat_at' => now()->subMinutes(10),
    ])->save();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('automation.anonymizationMonths', 12)
            ->where('automation.scheduler.status', 'healthy')
            ->where('automation.queue.status', 'stale')
            ->where('automation.queue.connection', 'sync')
            ->where('automation.pendingAnonymizationCount', 0));
});

it('runs a real scheduler to queue health check', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.automation.check'))
        ->assertRedirect(route('admin.users.index'));

    $settings = SystemSetting::current();

    expect($settings->automation_check_requested_at)
        ->not->toBeNull()
        ->and($settings->automation_check_completed_at)->toBeNull();

    $this->artisan('automation:heartbeat')->assertSuccessful();

    expect($settings->refresh()->automation_check_completed_at)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('automation.healthCheck.status', 'passed'));
});

it('does not pass a check with queue work created before the request', function () {
    $admin = User::factory()->admin()->create();
    $staleHeartbeatJob = new RecordQueueHeartbeat;

    $this->actingAs($admin)
        ->post(route('admin.users.automation.check'))
        ->assertRedirect(route('admin.users.index'));

    $staleHeartbeatJob->handle();

    expect(SystemSetting::current()->automation_check_completed_at)->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('automation.healthCheck.status', 'pending'));
});

it('records the scheduler heartbeat and dispatches a queue heartbeat', function () {
    Queue::fake();
    $this->travelTo('2026-08-17 12:00:00');

    $this->artisan('automation:heartbeat')->assertSuccessful();

    expect(SystemSetting::current()->scheduler_heartbeat_at?->toDateTimeString())
        ->toBe('2026-08-17 12:00:00');
    Queue::assertPushed(RecordQueueHeartbeat::class);
});

it('records the queue heartbeat when its job is processed', function () {
    $this->travelTo('2026-08-17 12:05:00');

    (new RecordQueueHeartbeat)->handle();

    expect(SystemSetting::current()->queue_heartbeat_at?->toDateTimeString())
        ->toBe('2026-08-17 12:05:00');
});

it('schedules heartbeat and anonymization commands', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())
        ->toContain('automation:heartbeat')
        ->toContain('users:anonymize-deactivated');
});

it('anonymizes expired accounts while preserving attendance statistics', function () {
    config()->set('session.driver', 'database');
    SystemSetting::current()->update(['user_anonymization_months' => 1]);
    $this->travelTo('2026-08-17 12:00:00');

    $expiredUser = User::factory()->admin()->create([
        'name' => 'Former Tutor',
        'email' => 'former@example.com',
        'two_factor_secret' => 'secret',
        'two_factor_recovery_codes' => 'codes',
        'two_factor_confirmed_at' => now(),
    ]);
    $attendance = Attendance::factory()->for($expiredUser)->create();
    $expiredUser->delete();
    User::withTrashed()->whereKey($expiredUser)->update([
        'deleted_at' => now()->subMonthsNoOverflow()->subDay(),
    ]);

    $recentUser = User::factory()->create(['email' => 'recent@example.com']);
    $recentUser->delete();
    User::withTrashed()->whereKey($recentUser)->update([
        'deleted_at' => now()->subMonthsNoOverflow()->addDay(),
    ]);

    DB::table('sessions')->insert([
        'id' => 'expired-user-session',
        'user_id' => $expiredUser->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    $this->artisan('users:anonymize-deactivated')
        ->expectsOutput('1 Benutzer anonymisiert.')
        ->assertSuccessful();

    $anonymizedUser = User::withTrashed()->findOrFail($expiredUser->id);
    $stillDeactivatedUser = User::withTrashed()->findOrFail($recentUser->id);

    expect($anonymizedUser->name)
        ->toBe('Anonymisierter Account')
        ->and($anonymizedUser->email)->toEndWith('@example.invalid')
        ->and($anonymizedUser->email)->not->toBe('former@example.com')
        ->and(Hash::check('password', $anonymizedUser->password))->toBeFalse()
        ->and($anonymizedUser->isMod)->toBeFalse()
        ->and($anonymizedUser->isAdmin)->toBeFalse()
        ->and($anonymizedUser->email_verified_at)->toBeNull()
        ->and($anonymizedUser->two_factor_secret)->toBeNull()
        ->and($anonymizedUser->two_factor_recovery_codes)->toBeNull()
        ->and($anonymizedUser->two_factor_confirmed_at)->toBeNull()
        ->and($anonymizedUser->anonymized_at)->not->toBeNull()
        ->and($stillDeactivatedUser->email)->toBe('recent@example.com')
        ->and($stillDeactivatedUser->anonymized_at)->toBeNull()
        ->and($attendance->fresh()->user?->name)->toBe('Anonymisierter Account');

    $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    $this->assertDatabaseMissing('sessions', ['id' => 'expired-user-session']);
});
