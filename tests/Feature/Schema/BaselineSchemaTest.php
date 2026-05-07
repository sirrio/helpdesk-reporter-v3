<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('builds the rebuild baseline schema from migrations', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasColumns('users', [
        'name',
        'email',
        'password',
        'isMod',
        'isAdmin',
        'deleted_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ]))->toBeTrue();

    expect(Schema::hasTable('password_reset_tokens'))->toBeTrue();
    expect(Schema::hasTable('sessions'))->toBeTrue();
    expect(Schema::hasTable('cache'))->toBeTrue();
    expect(Schema::hasTable('cache_locks'))->toBeTrue();
    expect(Schema::hasTable('jobs'))->toBeTrue();
    expect(Schema::hasTable('job_batches'))->toBeTrue();
    expect(Schema::hasTable('failed_jobs'))->toBeTrue();

    expect(Schema::hasTable('semesters'))->toBeTrue();
    expect(Schema::hasColumns('semesters', [
        'semester',
        'start',
        'end',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasTable('degrees'))->toBeTrue();
    expect(Schema::hasColumns('degrees', [
        'name',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasTable('faculties'))->toBeTrue();
    expect(Schema::hasColumns('faculties', [
        'name',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasTable('attendances'))->toBeTrue();
    expect(Schema::hasColumns('attendances', [
        'user_id',
        'semester',
        'date',
        'startTime',
        'endTime',
        'degree',
        'faculty',
        'mathBasic',
        'mathFractions',
        'mathLow',
        'mathHigh',
        'programming',
        'physics',
        'chemistry',
        'organization',
        'online',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasTable('personal_access_tokens'))->toBeFalse();
});
