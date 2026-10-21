<?php

namespace Database\Factories;

use App\Domain\Applications\Enums\InterviewMode;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'scheduled_at' => now()->addDays(3)->setTime(10, 0),
            'duration_minutes' => 30,
            'mode' => InterviewMode::Video,
            'location' => null,
            'instructions' => null,
        ];
    }
}
