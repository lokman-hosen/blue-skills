<?php

namespace Database\Factories;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\JobSkillRequirement;
use App\Domain\Taxonomy\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobSkillRequirement>
 */
class JobSkillRequirementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => JobPosting::factory(),
            'skill_id' => Skill::factory(),
            'required' => true,
        ];
    }
}
