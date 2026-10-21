<?php

namespace Database\Factories;

use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohort>
 */
class CohortFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'organization_id' => fn (array $attributes) => Course::query()->findOrFail($attributes['course_id'])->organization_id,
            'name' => '2026 Cohort',
            'starts_on' => '2026-01-10',
            'ends_on' => '2026-06-30',
            'invite_code' => fn () => Cohort::newInviteCode(),
            'status' => CohortStatus::Open,
        ];
    }
}
