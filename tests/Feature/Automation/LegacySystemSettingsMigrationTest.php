<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('upgrades the legacy day-based automation settings schema', function () {
    Schema::drop('system_settings');
    Schema::create('system_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedSmallInteger('user_anonymization_days')->default(365);
        $table->timestamp('scheduler_heartbeat_at')->nullable();
        $table->timestamp('queue_heartbeat_at')->nullable();
        $table->timestamps();
    });

    DB::table('system_settings')->insert([
        'id' => 1,
        'user_anonymization_days' => 365,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migrationPaths = [
        database_path('migrations/2026_08_17_182825_add_legacy_compatibility_columns_to_system_settings_table.php'),
        database_path('migrations/2026_08_17_182830_convert_legacy_user_anonymization_days_to_months.php'),
        database_path('migrations/2026_08_17_182835_remove_legacy_user_anonymization_days_from_system_settings_table.php'),
    ];

    foreach ($migrationPaths as $migrationPath) {
        (require $migrationPath)->up();
    }

    expect(Schema::hasColumn('system_settings', 'user_anonymization_days'))->toBeFalse()
        ->and(Schema::hasColumn('system_settings', 'user_anonymization_months'))->toBeTrue()
        ->and(Schema::hasColumn('system_settings', 'automation_check_requested_at'))->toBeTrue()
        ->and(Schema::hasColumn('system_settings', 'automation_check_token'))->toBeTrue()
        ->and(Schema::hasColumn('system_settings', 'automation_check_completed_at'))->toBeTrue()
        ->and(Schema::hasColumn('system_settings', 'automation_check_completed_token'))->toBeTrue()
        ->and(DB::table('system_settings')->where('id', 1)->value('user_anonymization_months'))->toBe(12);
});
