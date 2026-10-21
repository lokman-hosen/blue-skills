<?php

namespace Database\Factories;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationStageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStageEvent>
 */
class ApplicationStageEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'from_status' => null,
            'to_status' => 'applied',
        ];
    }
}
