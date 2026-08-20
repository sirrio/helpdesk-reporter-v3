<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            ! Schema::hasColumn('system_settings', 'user_anonymization_days')
            || ! Schema::hasColumn('system_settings', 'user_anonymization_months')
        ) {
            return;
        }

        DB::table('system_settings')
            ->select(['id', 'user_anonymization_days'])
            ->orderBy('id')
            ->get()
            ->each(function (object $settings): void {
                $days = max(1, (int) $settings->user_anonymization_days);
                $months = min(120, max(1, (int) ceil($days / (365 / 12))));

                DB::table('system_settings')
                    ->where('id', $settings->id)
                    ->update(['user_anonymization_months' => $months]);
            });
    }

    /**
     * Reverse the migrations.
     *
     * Converting an arbitrary number of days to whole months is irreversible.
     */
    public function down(): void {}
};
