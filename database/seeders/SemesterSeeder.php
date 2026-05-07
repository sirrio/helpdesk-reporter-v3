<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['semester' => 'WS 2024/2025', 'start' => '2024-10-01', 'end' => '2025-03-31'],
            ['semester' => 'SS 2025', 'start' => '2025-04-01', 'end' => '2025-09-30'],
        ] as $semester) {
            Semester::query()->updateOrCreate(
                ['semester' => $semester['semester']],
                $semester,
            );
        }
    }
}
