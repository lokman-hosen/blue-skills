<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\Course;
use Spatie\LaravelData\Data;

/**
 * A course with its cohorts, newest first.
 */
class CourseData extends Data
{
    /**
     * @param  list<CohortSummaryData>  $cohorts
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $occupationId,
        public ?string $occupation,
        public ?int $durationWeeks,
        public ?string $description,
        public array $cohorts,
    ) {}

    public static function fromCourse(Course $course): self
    {
        return new self(
            id: $course->id,
            title: $course->title,
            occupationId: $course->occupation_id,
            occupation: $course->occupation?->translatedName(),
            durationWeeks: $course->duration_weeks,
            description: $course->description,
            cohorts: $course->cohorts->map(fn (Cohort $cohort) => CohortSummaryData::fromCohort($cohort->setRelation('course', $course)))->values()->all(),
        );
    }
}
