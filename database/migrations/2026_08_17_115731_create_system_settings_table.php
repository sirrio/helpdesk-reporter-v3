<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('user_anonymization_months')->default(12);
            $table->timestamp('scheduler_heartbeat_at')->nullable();
            $table->timestamp('queue_heartbeat_at')->nullable();
            $table->timestamp('automation_check_requested_at')->nullable();
            $table->uuid('automation_check_token')->nullable();
            $table->timestamp('automation_check_completed_at')->nullable();
            $table->uuid('automation_check_completed_token')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'id' => 1,
            'user_anonymization_months' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
