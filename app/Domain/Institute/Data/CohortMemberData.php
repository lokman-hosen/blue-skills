<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Models\CohortMember;
use App\Domain\Worker\Models\SkillShow;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A graduate as the institute sees them (product rule 11): the roster line it typed until the
 * worker accepts, then the worker's name and Skill Passport number, never their documents or
 * contact details.
 */
class CohortMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $cohortId,
        public string $cohortName,
        public string $courseTitle,
        public ?string $cohortEndsOn,
        public string $name,
        /** Translated via `status.member_{value}`. */
        public StatusData $status,
        public string $source,
        /** Only for roster lines nobody answered yet: the institute typed it. */
        public ?string $rosterPhone,
        public ?string $passportReference,
        public ?string $respondedAt,
        public bool $sharesProgress,
        public bool $hasSkillShow,
        public ?IssuedRecordData $attestation = null,
        /** The latest assessment still issued. */
        public ?IssuedRecordData $assessment = null,
    ) {}

    public static function fromMember(CohortMember $member): self
    {
        $profile = $member->isAccepted() ? $member->workerProfile : null;

        return new self(
            id: $member->id,
            cohortId: $member->cohort_id,
            cohortName: $member->cohort->name,
            courseTitle: $member->cohort->course->title,
            cohortEndsOn: $member->cohort->ends_on?->toDateString(),
            name: $profile?->user->name ?? $member->roster_name ?? '',
            status: new StatusData('member_'.$member->status->value, $member->status->label(), $member->status->tone()),
            source: $member->source->value,
            rosterPhone: $member->worker_profile_id === null ? $member->roster_phone : null,
            passportReference: $profile?->reference,
            respondedAt: $member->responded_at?->toIso8601String(),
            sharesProgress: $member->isAccepted() && $member->shares_progress,
            hasSkillShow: $profile !== null && $profile->skillShows->contains(fn (SkillShow $skillShow) => $skillShow->isPublished()),
            attestation: ($attestation = $member->attestations->sortByDesc('created_at')->first()) !== null
                ? IssuedRecordData::fromAttestation($attestation)
                : null,
            assessment: ($assessment = $member->assessments->sortByDesc('assessed_on')->first()) !== null
                ? IssuedRecordData::fromAssessment($assessment)
                : null,
        );
    }
}
