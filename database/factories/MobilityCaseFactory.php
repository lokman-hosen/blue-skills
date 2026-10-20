<?php

namespace Database\Factories;

use App\Domain\Applications\Models\Application;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Open;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobilityCase>
 */
class MobilityCaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'template' => 'AE',
            'current_stage' => 'job_selected',
            'status' => Open::class,
        ];
    }
}
