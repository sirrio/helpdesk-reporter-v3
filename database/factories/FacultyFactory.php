<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Naturwissenschaften',
                'Informatik',
                'Ingenieurwissenschaften',
                'Wirtschaft',
                'Sozialwissenschaften',
            ]),
        ];
    }
}
