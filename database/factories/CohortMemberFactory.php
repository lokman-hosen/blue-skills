<?php

namespace Database\Factories;

use App\Domain\Institute\Enums\CohortMemberSource;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CohortMember>
 */
class CohortMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_id' => Cohort::factory(),
            'roster_name' => fake()->name(),
            'roster_phone' => '+88017'.fake()->unique()->numerify('########'),
            'status' => CohortMemberStatus::Invited,
            'source' => CohortMemberSource::Roster,
        ];
    }

    public function accepted(?WorkerProfile $profile = null, bool $sharesProgress = false): static
    {
        return $this->state(fn () => [
            'worker_profile_id' => $profile !== null ? $profile->id : WorkerProfile::factory(),
            'status' => CohortMemberStatus::Accepted,
            'source' => CohortMemberSource::Link,
            'shares_progress' => $sharesProgress,
            'responded_at' => now(),
        ]);
    }
}
