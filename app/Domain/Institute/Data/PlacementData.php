<?php

namespace App\Domain\Institute\Data;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Institute\Models\CohortMember;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A graduate who shares their progress with the institute (D-082): employer interest
 * (invitations to apply) and their applications up to joining. Nothing for graduates who don't
 * share it.
 */
class PlacementData extends Data
{
    /**
     * @param  list<array{organization: string, job: string, sentAt: string}>  $interest
     * @param  list<array{job: string, jobCode: string, employer: string, status: StatusData, appliedAt: string, caseStatus: StatusData|null}>  $applications
     */
    public function __construct(
        public string $memberId,
        public string $name,
        public string $passportReference,
        public string $cohort,
        public string $course,
        public array $interest,
        public array $applications,
        public bool $placed,
    ) {}

    public static function fromMember(CohortMember $member): self
    {
        $profile = $member->workerProfile;

        $applications = $profile?->applications->map(function (Application $application): array {
            $case = $application->mobilityCase;
            /** @var string $caseStatus */
            $caseStatus = $case?->status->getValue() ?? '';

            return [
                'job' => $application->job->title,
                'jobCode' => $application->job->public_code,
                'employer' => (string) ($application->job->employer->display_name ?? $application->job->organization->display_name),
                'status' => StatusData::fromState($application->status),
                'appliedAt' => $application->applied_at->toIso8601String(),
                'caseStatus' => $case !== null ? new StatusData('case_'.$caseStatus, $case->status->label(), $case->status->tone()) : null,
            ];
        })->values()->all() ?? [];

        return new self(
            memberId: $member->id,
            name: (string) $profile?->user?->name,
            passportReference: (string) $profile?->reference,
            cohort: $member->cohort->name,
            course: $member->cohort->course->title,
            interest: $profile?->jobInvitations->map(fn (JobInvitation $invitation) => [
                'organization' => (string) $invitation->organization->display_name,
                'job' => $invitation->job->title,
                'sentAt' => $invitation->created_at?->toIso8601String() ?? '',
            ])->values()->all() ?? [],
            applications: $applications,
            placed: collect($applications)->contains(fn (array $row) => $row['caseStatus']?->value === 'case_completed'),
        );
    }
}
