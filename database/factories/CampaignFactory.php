<?php

namespace Database\Factories;

use App\Domain\Crm\Enums\CampaignChannel;
use App\Domain\Crm\Enums\CampaignLanding;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\States\Campaign\Active;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Dhaka job fair '.fake()->unique()->numberBetween(1, 9999);

        return [
            'code' => fn () => Campaign::newCode($name),
            'name' => $name,
            'channel' => CampaignChannel::JobFair,
            'source_country_code' => 'BD',
            'landing' => CampaignLanding::Join,
            'status' => Active::class,
            'starts_on' => now()->subMonth()->toDateString(),
        ];
    }
}
