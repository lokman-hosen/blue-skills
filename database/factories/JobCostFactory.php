<?php

namespace Database\Factories;

use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobCost>
 */
class JobCostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => JobPosting::factory(),
            'category' => JobCostCategory::Visa,
            'description' => 'Visa application fee',
            'amount_minor' => fake()->numberBetween(50, 300) * 100,
            'currency' => 'EUR',
            'payer' => CostPayer::Employer,
        ];
    }
}
