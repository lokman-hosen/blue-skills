<?php

namespace Database\Factories;

use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobilityCaseStage>
 */
class MobilityCaseStageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobility_case_id' => MobilityCase::factory(),
            'key' => fake()->unique()->slug(2),
            'position' => fake()->numberBetween(1, 10),
            'status' => MobilityStageStatus::Pending,
        ];
    }
}
