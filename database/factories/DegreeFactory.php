<?php

namespace Database\Factories;

use App\Models\Degree;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Degree>
 */
class DegreeFactory extends Factory
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
                'Informatik',
                'Mathematik',
                'Physik',
                'Chemie',
                'Maschinenbau',
                'Elektrotechnik',
            ]),
        ];
    }
}
