<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'semester' => sprintf('WS %d/%d', fake()->numberBetween(2024, 2028), fake()->numberBetween(2029, 2033)),
            'date' => fake()->date(),
            'startTime' => fake()->time('H:i:s'),
            'endTime' => fake()->time('H:i:s'),
            'degree' => fake()->randomElement(['Informatik', 'Mathematik', 'Physik']),
            'faculty' => fake()->randomElement(['Naturwissenschaften', 'Informatik', 'Ingenieurwissenschaften']),
            'mathBasic' => fake()->boolean(),
            'mathFractions' => fake()->boolean(),
            'mathLow' => fake()->boolean(),
            'mathHigh' => fake()->boolean(),
            'programming' => fake()->boolean(),
            'physics' => fake()->boolean(),
            'chemistry' => fake()->boolean(),
            'organization' => fake()->boolean(),
            'online' => fake()->boolean(),
        ];
    }

    /**
     * Match the attendance to a semester record.
     */
    public function forSemester(Semester $semester): static
    {
        return $this->state(fn (array $attributes) => [
            'semester' => $semester->semester,
        ]);
    }

    /**
     * Match the attendance to a degree record.
     */
    public function forDegree(Degree $degree): static
    {
        return $this->state(fn (array $attributes) => [
            'degree' => $degree->name,
        ]);
    }

    /**
     * Match the attendance to a faculty record.
     */
    public function forFaculty(Faculty $faculty): static
    {
        return $this->state(fn (array $attributes) => [
            'faculty' => $faculty->name,
        ]);
    }
}
