<?php

namespace Database\Seeders;

use App\Models\Degree;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class DegreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Informatik' => 'Informatik',
            'Mathematik' => 'Naturwissenschaften',
            'Physik' => 'Ingenieurwissenschaften',
        ] as $degree => $faculty) {
            Degree::query()->updateOrCreate(
                ['name' => $degree],
                ['faculty_id' => Faculty::query()->where('name', $faculty)->firstOrFail()->id],
            );
        }
    }
}
