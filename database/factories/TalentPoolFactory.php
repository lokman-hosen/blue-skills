<?php

namespace Database\Factories;

use App\Domain\Institute\Models\TalentPool;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalentPool>
 */
class TalentPoolFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->institute()->verified(),
            'name' => 'Electricians ready to travel',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => now()]);
    }
}
