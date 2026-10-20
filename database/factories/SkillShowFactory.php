<?php

namespace Database\Factories;

use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillShow>
 */
class SkillShowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'duration_ms' => fake()->numberBetween(10_000, 30_000),
            'visibility' => ProfileVisibility::EmployersAndAgencies,
            'moderation_status' => ModerationStatus::Pending,
        ];
    }

    public function processing(): static
    {
        return $this->state(['duration_ms' => null, 'moderation_status' => ModerationStatus::Processing]);
    }

    public function approved(): static
    {
        return $this->state(['moderation_status' => ModerationStatus::Approved, 'moderated_at' => now()]);
    }
}
