<?php

namespace Database\Factories;

use App\Domain\Institute\Enums\AssessmentResult;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TradeAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TradeAssessment>
 */
class TradeAssessmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_member_id' => CohortMember::factory()->accepted(),
            'organization_id' => fn (array $attributes) => CohortMember::query()->findOrFail($attributes['cohort_member_id'])->cohort->organization_id,
            'worker_profile_id' => fn (array $attributes) => CohortMember::query()->findOrFail($attributes['cohort_member_id'])->worker_profile_id,
            'title' => 'Practical wiring test',
            'result' => AssessmentResult::Pass,
            'score' => 82,
            'assessed_on' => '2026-06-20',
            'status' => IssuedRecordStatus::Issued,
        ];
    }
}
