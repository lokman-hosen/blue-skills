<?php

namespace Database\Factories;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Consent;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consent>
 */
class ConsentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'purpose' => ConsentPurpose::Discovery,
            'granted_at' => now(),
        ];
    }
}
