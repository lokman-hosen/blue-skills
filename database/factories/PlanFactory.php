<?php

namespace Database\Factories;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Organization\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'employer-'.fake()->unique()->numberBetween(1, 99999),
            'name' => 'Employer Standard',
            'organization_type' => OrganizationType::Employer,
            'price_amount_minor' => 150_000,
            'price_currency' => 'AED',
            'billing_period' => BillingPeriod::Monthly,
            'posting_credits' => 10,
            'seats' => 5,
            'talent_search' => true,
            'recruitment_projects' => false,
            'is_active' => true,
        ];
    }
}
