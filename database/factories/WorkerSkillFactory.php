<?php

namespace Database\Factories;

use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Models\WorkerSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerSkill>
 */
class WorkerSkillFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'skill_id' => Skill::factory(),
            'source' => SkillSource::Typed,
            'confirmed_at' => now(),
        ];
    }
}
