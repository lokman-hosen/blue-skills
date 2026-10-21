<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Enums\AssessmentResult;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Enums\ModerationStatus;
use Spatie\LaravelData\Data;

/**
 * Institute dashboard tiles (prototype i-cohort KPIs).
 */
class InstituteCountsData extends Data
{
    public function __construct(
        public int $openCohorts,
        public int $graduates,
        public int $rosterWaiting,
        public int $skillShows,
        public int $attested,
        public int $assessmentsPassed,
        public int $sharingProgress,
    ) {}

    public static function forInstitute(Organization $institute): self
    {
        $members = fn () => CohortMember::query()->whereHas('cohort', fn ($cohorts) => $cohorts->where('organization_id', $institute->id));

        return new self(
            openCohorts: $institute->cohorts()->where('status', CohortStatus::Open)->count(),
            graduates: $members()->accepted()->distinct()->count('worker_profile_id'),
            rosterWaiting: $members()->where('status', CohortMemberStatus::Invited)->count(),
            skillShows: $members()->accepted()
                ->whereHas('workerProfile.skillShows', fn ($skillShows) => $skillShows->where('moderation_status', ModerationStatus::Approved))
                ->distinct()->count('worker_profile_id'),
            attested: TrainingAttestation::query()->where('organization_id', $institute->id)->where('status', IssuedRecordStatus::Issued)->count(),
            assessmentsPassed: TradeAssessment::query()->where('organization_id', $institute->id)
                ->where('status', IssuedRecordStatus::Issued)->where('result', AssessmentResult::Pass)->count(),
            sharingProgress: $members()->accepted()->where('shares_progress', true)->distinct()->count('worker_profile_id'),
        );
    }
}
