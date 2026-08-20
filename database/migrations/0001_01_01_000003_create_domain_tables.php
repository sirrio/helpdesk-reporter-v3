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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('semester')->unique();
            $table->date('start');
            $table->date('end');
            $table->softDeletes();
        });

        Schema::create('degrees', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->softDeletes();
        });

        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->softDeletes();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('semester');
            $table->date('date');
            $table->time('startTime');
            $table->time('endTime');
            $table->string('degree');
            $table->string('faculty');
            $table->boolean('mathBasic');
            $table->boolean('mathFractions');
            $table->boolean('mathLow');
            $table->boolean('mathHigh');
            $table->boolean('programming');
            $table->boolean('physics');
            $table->boolean('chemistry');
            $table->boolean('organization');
            $table->boolean('online')->default(true);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('faculties');
        Schema::dropIfExists('degrees');
        Schema::dropIfExists('semesters');
    }
};
