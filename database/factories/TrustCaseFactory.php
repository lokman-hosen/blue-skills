<?php

namespace Database\Factories;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\TrustCase\Open;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrustCase>
 */
class TrustCaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $job = JobPosting::factory()->published();

        return [
            'reference' => fn () => TrustCase::newReference(),
            'subject_type' => 'job',
            'subject_id' => $job,
            'category' => ReportCategory::FakeJob,
            'priority' => CasePriority::Normal,
            'status' => Open::class,
            'summary' => 'Workers say the employer asked for a visa fee.',
        ];
    }
}
