<?php

namespace Database\Factories;

use App\Domain\Worker\Models\WorkerDestinationPreference;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerDestinationPreference>
 */
class WorkerDestinationPreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'country_code' => fn () => CountryFactory::existingCode(),
            'priority' => 1,
        ];
    }
}
