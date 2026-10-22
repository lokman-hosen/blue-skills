<?php

namespace Database\Factories;

use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\States\Subscription\Active;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationSubscription>
 */
class OrganizationSubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->employer()->verified(),
            'plan_id' => Plan::factory(),
            'status' => Active::class,
            'starts_on' => now()->startOfMonth()->toDateString(),
        ];
    }
}
