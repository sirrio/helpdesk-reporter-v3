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
        if (! Schema::hasColumn('system_settings', 'user_anonymization_days')) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('user_anonymization_days');
        });
    }

    /**
     * Reverse the migrations.
     *
     * The removed day value was intentionally normalized to whole months.
     */
    public function down(): void {}
};
