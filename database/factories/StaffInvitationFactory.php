<?php

namespace Database\Factories;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\StaffInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StaffInvitation>
 */
class StaffInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'role' => StaffRole::VerificationOfficer,
            'token_hash' => StaffInvitation::hashToken(Str::random(48)),
            'expires_at' => now()->addDays(StaffInvitation::VALID_DAYS),
        ];
    }
}
