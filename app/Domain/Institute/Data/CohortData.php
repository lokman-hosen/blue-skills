<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Support\CohortInvite;
use Spatie\LaravelData\Data;

/**
 * A cohort's page (prototype i-cohort): who joined, and the link, QR code and WhatsApp text the
 * institute shares itself (D-082).
 */
class CohortData extends Data
{
    /**
     * @param  list<CohortMemberData>  $members
     */
    public function __construct(
        public CohortSummaryData $summary,
        public string $courseId,
        public string $inviteCode,
        public string $inviteUrl,
        public string $whatsappText,
        public string $qrSvg,
        public int $skillShows,
        public array $members,
    ) {}

    public static function fromCohort(Cohort $cohort): self
    {
        $cohort->loadMissing(['course', 'organization', 'members.workerProfile.user', 'members.workerProfile.skillShows', 'members.attestations', 'members.assessments']);
        $members = $cohort->members->sortBy(fn (CohortMember $member) => [$member->status->value !== 'accepted', $member->roster_name ?? ''])
            ->map(fn (CohortMember $member) => CohortMemberData::fromMember($member))->values();

        return new self(
            summary: CohortSummaryData::fromCohort($cohort),
            courseId: $cohort->course_id,
            inviteCode: $cohort->invite_code,
            inviteUrl: CohortInvite::url($cohort),
            whatsappText: CohortInvite::whatsappText($cohort),
            qrSvg: CohortInvite::qrSvg($cohort),
            skillShows: $members->filter(fn (CohortMemberData $member) => $member->hasSkillShow)->count(),
            members: $members->all(),
        );
    }
}
