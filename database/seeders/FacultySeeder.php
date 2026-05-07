<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Naturwissenschaften', 'Informatik', 'Ingenieurwissenschaften'] as $faculty) {
            Faculty::query()->firstOrCreate(['name' => $faculty]);
        }
    }
}
