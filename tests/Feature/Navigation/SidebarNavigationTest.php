<?php

use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shares attendance semesters for the sidebar in descending start order', function () {
    $user = User::factory()->create();

    Semester::factory()->create([
        'semester' => 'WS 2024/2025',
        'start' => '2024-10-01',
    ]);

    Semester::factory()->create([
        'semester' => 'SS 2025',
        'start' => '2025-04-01',
    ]);

    Semester::factory()->create([
        'semester' => 'WS 2025/2026',
        'start' => '2025-10-01',
    ]);

    Degree::factory()->create(['name' => 'Informatik']);
    Faculty::factory()->create(['name' => 'Naturwissenschaften']);

    $this->actingAs($user)
        ->get(route('attendances.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendances/index')
            ->where('navigation.attendanceSemesters', [
                'WS 2025/2026',
                'SS 2025',
                'WS 2024/2025',
            ]));
});
