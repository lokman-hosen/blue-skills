<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\Application;
use Spatie\LaravelData\Data;

/**
 * An applicant in the organization-wide candidate list (agency "Candidates", D-080): the job's
 * pipeline row plus which job it is for and whether a mobility case is open.
 */
class CandidateListItemData extends Data
{
    public function __construct(
        public ApplicantListItemData $applicant,
        public string $jobTitle,
        public string $jobCode,
        public string $destinationCountryCode,
        public ?string $mobilityCaseId,
    ) {}

    public static function fromApplication(Application $application): self
    {
        return new self(
            applicant: ApplicantListItemData::fromApplication($application),
            jobTitle: $application->job->title,
            jobCode: $application->job->public_code,
            destinationCountryCode: $application->job->destination_country_code,
            mobilityCaseId: $application->mobilityCase?->id,
        );
    }
}
