<?php

namespace Database\Factories;

use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobMandate>
 */
class JobMandateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employer_organization_id' => Organization::factory()->employer(),
            'agency_organization_id' => Organization::factory()->agency(),
            'reference' => fake()->unique()->bothify('MD-####'),
            'headcount' => fake()->numberBetween(5, 200),
            'valid_from' => now(),
            'valid_until' => now()->addYear(),
            'status' => MandateStatus::Pending,
        ];
    }
}
