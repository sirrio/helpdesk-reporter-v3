<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $missingAnonymizationMonths = ! Schema::hasColumn('system_settings', 'user_anonymization_months');
        $missingCheckRequestedAt = ! Schema::hasColumn('system_settings', 'automation_check_requested_at');
        $missingCheckToken = ! Schema::hasColumn('system_settings', 'automation_check_token');
        $missingCheckCompletedAt = ! Schema::hasColumn('system_settings', 'automation_check_completed_at');
        $missingCheckCompletedToken = ! Schema::hasColumn('system_settings', 'automation_check_completed_token');

        Schema::table('system_settings', function (Blueprint $table) use (
            $missingAnonymizationMonths,
            $missingCheckRequestedAt,
            $missingCheckToken,
            $missingCheckCompletedAt,
            $missingCheckCompletedToken,
        ) {
            if ($missingAnonymizationMonths) {
                $table->unsignedTinyInteger('user_anonymization_months')->default(12);
            }

            if ($missingCheckRequestedAt) {
                $table->timestamp('automation_check_requested_at')->nullable();
            }

            if ($missingCheckToken) {
                $table->uuid('automation_check_token')->nullable();
            }

            if ($missingCheckCompletedAt) {
                $table->timestamp('automation_check_completed_at')->nullable();
            }

            if ($missingCheckCompletedToken) {
                $table->uuid('automation_check_completed_token')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * This conditional compatibility repair cannot know which columns predated it.
     */
    public function down(): void {}
};
