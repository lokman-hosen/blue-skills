<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Jobs\Data\JobListingData;
use Spatie\LaravelData\Data;

class JobInvitationData extends Data
{
    public function __construct(
        public string $id,
        public JobListingData $job,
        public string $organization,
        public ?string $note,
        /** An unanswered invitation to a job that no longer takes applications shows as expired. */
        public InvitationStatus $status,
        public string $sentAt,
    ) {}

    public static function fromInvitation(JobInvitation $invitation): self
    {
        $status = $invitation->status === InvitationStatus::Sent && ! $invitation->job->acceptsApplications()
            ? InvitationStatus::Expired
            : $invitation->status;

        return new self(
            id: $invitation->id,
            job: JobListingData::fromJob($invitation->job),
            organization: $invitation->organization->display_name,
            note: $invitation->note,
            status: $status,
            sentAt: $invitation->created_at?->toIso8601String() ?? '',
        );
    }
}
