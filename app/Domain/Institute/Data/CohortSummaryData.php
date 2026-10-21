<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A cohort in its course's list: dates, whether it takes graduates, and who joined.
 */
class CohortSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $courseTitle,
        public ?string $startsOn,
        public ?string $endsOn,
        /** Translated via `status.cohort_{value}`. */
        public StatusData $status,
        public int $joined,
        public int $invited,
    ) {}

    public static function fromCohort(Cohort $cohort): self
    {
        $counts = $cohort->relationLoaded('members')
            ? $cohort->members->countBy(fn ($member) => $member->status->value)
            : $cohort->members()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return new self(
            id: $cohort->id,
            name: $cohort->name,
            courseTitle: $cohort->course->title,
            startsOn: $cohort->starts_on?->toDateString(),
            endsOn: $cohort->ends_on?->toDateString(),
            status: new StatusData('cohort_'.$cohort->status->value, $cohort->status->label(), $cohort->status->tone()),
            joined: (int) ($counts[CohortMemberStatus::Accepted->value] ?? 0),
            invited: (int) ($counts[CohortMemberStatus::Invited->value] ?? 0),
        );
    }
}
