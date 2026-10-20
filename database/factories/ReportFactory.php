<?php

namespace Database\Factories;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\States\Report\Open;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_user_id' => User::factory()->worker(),
            'subject_type' => 'job',
            'subject_id' => JobPosting::factory(),
            'category' => ReportCategory::MisleadingSalary,
            'description' => fake()->paragraph(),
            'status' => Open::class,
        ];
    }
}
