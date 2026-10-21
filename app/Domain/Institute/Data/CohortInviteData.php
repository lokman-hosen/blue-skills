<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Models\Cohort;
use Spatie\LaravelData\Data;

/**
 * What a cohort invitation shows before anyone signs in: who invites, to which course, and
 * whether it still takes graduates.
 */
class CohortInviteData extends Data
{
    public function __construct(
        public string $code,
        public string $institute,
        public bool $instituteVerified,
        public string $course,
        public ?string $occupation,
        public string $cohort,
        public ?string $startsOn,
        public ?string $endsOn,
        public bool $open,
    ) {}

    public static function fromCohort(Cohort $cohort): self
    {
        $cohort->loadMissing(['organization', 'course.occupation']);

        return new self(
            code: $cohort->invite_code,
            institute: (string) $cohort->organization->display_name,
            instituteVerified: $cohort->organization->isVerified(),
            course: $cohort->course->title,
            occupation: $cohort->course->occupation?->translatedName(),
            cohort: $cohort->name,
            startsOn: $cohort->starts_on?->toDateString(),
            endsOn: $cohort->ends_on?->toDateString(),
            open: $cohort->isOpen(),
        );
    }
}
