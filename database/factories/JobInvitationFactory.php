<?php

namespace Database\Factories;

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobInvitation>
 */
class JobInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => JobPosting::factory()->published(),
            'worker_profile_id' => WorkerProfile::factory(),
            'organization_id' => fn (array $attributes) => JobPosting::query()->findOrFail($attributes['job_id'])->organization_id,
            'status' => InvitationStatus::Sent,
        ];
    }
}
