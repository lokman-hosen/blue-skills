<?php

namespace Database\Factories;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ProfileSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileSnapshot>
 */
class ProfileSnapshotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'data' => ['headline' => fake()->jobTitle()],
            'taken_at' => now(),
        ];
    }
}
