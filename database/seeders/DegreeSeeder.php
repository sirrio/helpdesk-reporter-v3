<?php

namespace Database\Seeders;

use App\Models\Degree;
use Illuminate\Database\Seeder;

class DegreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Informatik', 'Mathematik', 'Physik'] as $degree) {
            Degree::query()->firstOrCreate(['name' => $degree]);
        }
    }
}
