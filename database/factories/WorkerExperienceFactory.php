<?php

namespace Database\Factories;

use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerExperience>
 */
class WorkerExperienceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-10 years', '-2 years');

        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'country_code' => fn () => CountryFactory::existingCode(),
            'employer_name' => fake()->optional()->company(),
            'role' => fake()->jobTitle(),
            'started_on' => $start,
            'ended_on' => fake()->dateTimeBetween($start, 'now'),
        ];
    }
}
