<?php

namespace Database\Factories;

use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * A hand-entered lead who hasn't signed up.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+88017'.fake()->unique()->numerify('########'),
            'source' => LeadSource::WalkIn,
            'country_code' => 'BD',
            'follow_up_status' => FollowUpStatus::New,
            'conversion_stage' => ConversionStage::Prospect,
        ];
    }

    public function forWorker(?User $user = null): static
    {
        return $this->state(fn () => [
            'user_id' => $user !== null ? $user->id : User::factory()->worker(),
            'name' => null,
            'phone' => null,
            'source' => LeadSource::Direct,
            'conversion_stage' => ConversionStage::Registered,
            'stage_reached_at' => now(),
        ]);
    }
}
