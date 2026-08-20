<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    protected static int $year = 2024;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = self::$year++;
        $isWinterSemester = fake()->boolean();
        $start = $isWinterSemester
            ? now()->setDate($year, 10, 1)->startOfDay()
            : now()->setDate($year, 4, 1)->startOfDay();

        return [
            'semester' => $isWinterSemester
                ? sprintf('WS %d/%d', $year, $year + 1)
                : sprintf('SS %d', $year),
            'start' => $start->toDateString(),
            'end' => $start->copy()->addMonths(6)->subDay()->toDateString(),
        ];
    }
}
