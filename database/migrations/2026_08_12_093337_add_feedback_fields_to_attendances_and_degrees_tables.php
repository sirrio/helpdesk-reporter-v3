<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('degrees', function (Blueprint $table) {
            $table->foreignId('faculty_id')
                ->nullable()
                ->after('name')
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('visitors')->default(1)->after('online');
            $table->index(
                ['user_id', 'date', 'startTime', 'endTime'],
                'attendances_duplicate_lookup_index',
            );
        });

        DB::table('degrees')->orderBy('id')->each(function (object $degree): void {
            $facultyName = DB::table('attendances')
                ->where('degree', $degree->name)
                ->select('faculty', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('faculty')
                ->orderByDesc('aggregate')
                ->value('faculty');

            if ($facultyName === null) {
                return;
            }

            $facultyId = DB::table('faculties')
                ->where('name', $facultyName)
                ->value('id');

            if ($facultyId !== null) {
                DB::table('degrees')
                    ->where('id', $degree->id)
                    ->update(['faculty_id' => $facultyId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_duplicate_lookup_index');
            $table->dropColumn('visitors');
        });

        Schema::table('degrees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faculty_id');
        });
    }
};
