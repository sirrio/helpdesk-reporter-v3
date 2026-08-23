<?php

use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds at least three tutors with helpdesk entries', function () {
    $this->seed(DatabaseSeeder::class);

    expect(
        User::query()
            ->where('isAdmin', false)
            ->whereIn('email', [
                'test@example.com',
                'tutor.alpha@example.com',
                'tutor.beta@example.com',
                'tutor.gamma@example.com',
            ])
            ->count(),
    )->toBeGreaterThanOrEqual(3);

    expect(
        Attendance::query()
            ->distinct('user_id')
            ->count('user_id'),
    )->toBeGreaterThanOrEqual(3);

    expect(User::query()->whereNull('approved_at')->count())->toBe(0);
});
