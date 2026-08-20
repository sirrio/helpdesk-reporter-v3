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
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('date');
            $table->index(['semester', 'degree', 'faculty']);
            $table->boolean('online')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['semester', 'degree', 'faculty']);
            $table->boolean('online')->default(true)->change();
        });
    }
};
