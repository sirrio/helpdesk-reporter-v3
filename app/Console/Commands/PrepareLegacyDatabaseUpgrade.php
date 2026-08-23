<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:prepare-legacy-upgrade {--dry-run : Validate the database without changing its migration history}')]
#[Description('Prepare a verified legacy Helpdesk Reporter database for the consolidated migrations')]
class PrepareLegacyDatabaseUpgrade extends Command
{
    /** @var list<string> */
    private const BASELINE_MIGRATIONS = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
        '0001_01_01_000003_create_domain_tables',
    ];

    /** @var list<string> */
    private const LEGACY_MIGRATIONS = [
        '2014_10_12_000000_create_users_table',
        '2014_10_12_100000_create_password_resets_table',
        '2019_08_19_000000_create_failed_jobs_table',
        '2019_12_14_000001_create_personal_access_tokens_table',
        '2022_05_22_140727_create_attendances_table',
        '2022_05_24_183458_create_semesters_table',
        '2022_05_31_215115_create_degrees_table',
        '2022_05_31_215127_create_faculties_table',
        '2022_11_17_000839_add_softdeletes_to_attendances_table',
        '2023_03_17_162242_add_math_fractions_to_attendances_table',
        '2023_04_12_000000_add_expires_at_to_personal_access_tokens_table',
        '2023_04_12_000000_rename_password_resets_table',
        '2024_09_04_202741_create_cache_table',
        '2024_10_26_143515_add_remote_column_to_attendances_table',
        '2025_05_23_000001_add_softdeletes_to_users_table',
        '2025_05_23_000002_add_softdeletes_to_semesters_table',
        '2025_05_23_000003_add_softdeletes_to_degrees_table',
        '2025_05_23_000004_add_softdeletes_to_faculties_table',
        '2025_05_23_000005_rename_remote_to_online_in_attendances_table',
    ];

    /** @var array<string, list<string>> */
    private const REQUIRED_COLUMNS = [
        'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'isMod', 'isAdmin', 'deleted_at', 'created_at', 'updated_at'],
        'password_reset_tokens' => ['email', 'token', 'created_at'],
        'cache' => ['key', 'value', 'expiration'],
        'failed_jobs' => ['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at'],
        'semesters' => ['id', 'semester', 'start', 'end', 'deleted_at', 'created_at', 'updated_at'],
        'degrees' => ['id', 'name', 'deleted_at', 'created_at', 'updated_at'],
        'faculties' => ['id', 'name', 'deleted_at', 'created_at', 'updated_at'],
        'attendances' => ['id', 'user_id', 'semester', 'date', 'startTime', 'endTime', 'degree', 'faculty', 'mathBasic', 'mathFractions', 'mathLow', 'mathHigh', 'programming', 'physics', 'chemistry', 'organization', 'online', 'deleted_at', 'created_at', 'updated_at'],
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('migrations')) {
            return $this->fail('Abbruch: Die Migrationstabelle fehlt. Dies ist keine erkannte Legacy-Datenbank.');
        }

        $recordedMigrations = DB::table('migrations')->pluck('migration')->all();
        $recordedBaselines = array_values(array_intersect(self::BASELINE_MIGRATIONS, $recordedMigrations));

        if ($recordedBaselines === self::BASELINE_MIGRATIONS) {
            $this->info('Die konsolidierte Baseline ist bereits vollständig registriert. Keine Änderung erforderlich.');

            return self::SUCCESS;
        }

        if ($recordedBaselines !== []) {
            return $this->fail('Abbruch: Die konsolidierte Baseline ist nur teilweise registriert. Der Datenbankstand muss manuell geprüft werden.');
        }

        $missingLegacyMigrations = array_values(array_diff(self::LEGACY_MIGRATIONS, $recordedMigrations));

        if ($missingLegacyMigrations !== []) {
            return $this->fail('Abbruch: Nicht alle erwarteten Legacy-Migrationen sind registriert: '.implode(', ', $missingLegacyMigrations));
        }

        foreach (self::REQUIRED_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                return $this->fail("Abbruch: Die erwartete Legacy-Tabelle [{$table}] fehlt.");
            }

            $missingColumns = array_values(array_filter(
                $columns,
                fn (string $column): bool => ! Schema::hasColumn($table, $column),
            ));

            if ($missingColumns !== []) {
                return $this->fail("Abbruch: In [{$table}] fehlen erwartete Spalten: ".implode(', ', $missingColumns));
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Legacy-Datenbank erfolgreich geprüft. Die Baseline kann sicher registriert werden.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $nextBatch = ((int) DB::table('migrations')->max('batch')) + 1;

            DB::table('migrations')->insert(array_map(
                fn (string $migration): array => ['migration' => $migration, 'batch' => $nextBatch],
                self::BASELINE_MIGRATIONS,
            ));
        });

        $this->info('Legacy-Datenbank vorbereitet. Als Nächstes die regulären Migrationen ausführen.');

        return self::SUCCESS;
    }
}
