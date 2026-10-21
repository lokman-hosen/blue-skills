<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Models\CohortMember;
use Spatie\LaravelData\Data;

/**
 * An institute link on the worker's Privacy page ("My institutes"): what they joined and
 * whether the institute may follow their applications.
 */
class WorkerCohortData extends Data
{
    public function __construct(
        public string $id,
        public string $institute,
        public string $course,
        public string $cohort,
        public bool $sharesProgress,
        public ?string $joinedAt,
    ) {}

    public static function fromMember(CohortMember $member): self
    {
        return new self(
            id: $member->id,
            institute: (string) $member->cohort->organization->display_name,
            course: $member->cohort->course->title,
            cohort: $member->cohort->name,
            sharesProgress: $member->shares_progress,
            joinedAt: $member->responded_at?->toIso8601String(),
        );
    }
}
