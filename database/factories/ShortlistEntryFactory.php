<?php

namespace Database\Factories;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Applications\Models\ShortlistEntry;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShortlistEntry>
 */
class ShortlistEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shortlist_id' => Shortlist::factory(),
            'worker_profile_id' => WorkerProfile::factory(),
        ];
    }
}
