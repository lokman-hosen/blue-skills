<?php

namespace Database\Factories;

use App\Domain\Organization\Models\PilotInvite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PilotInvite>
 */
class PilotInviteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fn () => PilotInvite::newCode(),
            'partner_name' => 'Pilot partner '.fake()->unique()->numberBetween(1, 9999),
            'max_uses' => 1,
            'used_count' => 0,
        ];
    }
}
