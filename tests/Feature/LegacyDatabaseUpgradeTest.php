<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

const CONSOLIDATED_BASELINE_MIGRATIONS = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    '0001_01_01_000003_create_domain_tables',
];

function replaceBaselineHistoryWithLegacyHistory(): void
{
    DB::table('migrations')->whereIn('migration', CONSOLIDATED_BASELINE_MIGRATIONS)->delete();

    $nextBatch = ((int) DB::table('migrations')->max('batch')) + 1;
    $legacyMigrations = collect(glob(database_path('migrations/other/*.php')))
        ->map(fn (string $path): array => [
            'migration' => pathinfo($path, PATHINFO_FILENAME),
            'batch' => $nextBatch,
        ])
        ->all();

    DB::table('migrations')->insert($legacyMigrations);
}

it('validates a recognized legacy database without changing it', function () {
    replaceBaselineHistoryWithLegacyHistory();

    $this->artisan('app:prepare-legacy-upgrade', ['--dry-run' => true])
        ->expectsOutput('Legacy-Datenbank erfolgreich geprüft. Die Baseline kann sicher registriert werden.')
        ->assertSuccessful();

    expect(DB::table('migrations')->whereIn('migration', CONSOLIDATED_BASELINE_MIGRATIONS)->count())->toBe(0);
});

it('registers the consolidated baseline for a recognized legacy database', function () {
    replaceBaselineHistoryWithLegacyHistory();

    $previousBatch = (int) DB::table('migrations')->max('batch');

    $this->artisan('app:prepare-legacy-upgrade')
        ->expectsOutput('Legacy-Datenbank vorbereitet. Als Nächstes die regulären Migrationen ausführen.')
        ->assertSuccessful();

    $registeredBaselines = DB::table('migrations')
        ->whereIn('migration', CONSOLIDATED_BASELINE_MIGRATIONS)
        ->orderBy('migration')
        ->get(['migration', 'batch']);

    expect($registeredBaselines)->toHaveCount(4)
        ->and($registeredBaselines->pluck('migration')->all())->toBe(CONSOLIDATED_BASELINE_MIGRATIONS)
        ->and($registeredBaselines->pluck('batch')->unique()->all())->toBe([$previousBatch + 1]);
});

it('refuses an incomplete legacy migration history', function () {
    replaceBaselineHistoryWithLegacyHistory();
    DB::table('migrations')->where('migration', '2025_05_23_000005_rename_remote_to_online_in_attendances_table')->delete();

    $this->artisan('app:prepare-legacy-upgrade')
        ->expectsOutputToContain('Abbruch: Nicht alle erwarteten Legacy-Migrationen sind registriert')
        ->assertFailed();

    expect(DB::table('migrations')->whereIn('migration', CONSOLIDATED_BASELINE_MIGRATIONS)->count())->toBe(0);
});

it('refuses a partially registered consolidated baseline', function () {
    replaceBaselineHistoryWithLegacyHistory();
    DB::table('migrations')->insert([
        'migration' => CONSOLIDATED_BASELINE_MIGRATIONS[0],
        'batch' => ((int) DB::table('migrations')->max('batch')) + 1,
    ]);

    $this->artisan('app:prepare-legacy-upgrade')
        ->expectsOutputToContain('Abbruch: Die konsolidierte Baseline ist nur teilweise registriert')
        ->assertFailed();
});

it('adds framework tables that were absent from legacy installations', function () {
    foreach (['sessions', 'cache_locks', 'jobs', 'job_batches'] as $table) {
        Schema::drop($table);
    }

    (require database_path('migrations/2026_08_23_184121_create_missing_framework_tables_for_legacy_upgrades.php'))->up();

    expect(Schema::hasTable('sessions'))->toBeTrue()
        ->and(Schema::hasTable('cache_locks'))->toBeTrue()
        ->and(Schema::hasTable('jobs'))->toBeTrue()
        ->and(Schema::hasTable('job_batches'))->toBeTrue();
});
