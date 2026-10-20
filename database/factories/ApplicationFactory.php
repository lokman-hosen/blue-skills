<?php

namespace Database\Factories;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => JobPosting::factory(),
            'worker_profile_id' => WorkerProfile::factory(),
            'status' => Applied::class,
            'applied_at' => now(),
        ];
    }
}
