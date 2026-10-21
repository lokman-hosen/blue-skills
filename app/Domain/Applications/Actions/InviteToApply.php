<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\Support\TalentAccess;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Validation\ValidationException;

/**
 * An organization invites a worker it found in talent search to apply to one of its open jobs
 * (D-067 plan, 5e). A structured message with an optional short note; conversation starts only
 * once the worker applies. One invitation per job and worker, a daily cap per organization.
 */
class InviteToApply
{
    public function handle(Organization $organization, JobPosting $job, WorkerProfile $profile, ?string $note, User $actor): JobInvitation
    {
        if ($job->organization_id !== $organization->id || ! $job->acceptsApplications()) {
            throw ValidationException::withMessages(['job' => __('invitations.job_not_open')]);
        }

        if (! TalentAccess::canView($organization, $profile)) {
            throw ValidationException::withMessages(['job' => __('shortlists.not_visible')]);
        }

        if ($profile->applications()->where('job_id', $job->id)->exists()) {
            throw ValidationException::withMessages(['job' => __('invitations.already_applied')]);
        }

        if (JobInvitation::query()->where('job_id', $job->id)->where('worker_profile_id', $profile->id)->exists()) {
            throw ValidationException::withMessages(['job' => __('invitations.already_invited')]);
        }

        if (JobInvitation::query()->where('organization_id', $organization->id)->where('created_at', '>=', now()->subDay())->count() >= JobInvitation::DAILY_LIMIT) {
            throw ValidationException::withMessages(['job' => __('invitations.daily_limit', ['limit' => JobInvitation::DAILY_LIMIT])]);
        }

        $invitation = JobInvitation::query()->create([
            'job_id' => $job->id,
            'worker_profile_id' => $profile->id,
            'organization_id' => $organization->id,
            'invited_by' => $actor->id,
            'note' => $note,
            'status' => InvitationStatus::Sent,
        ]);

        activity('applications')->performedOn($invitation)->causedBy($actor)
            ->withProperties(['job' => $job->public_code, 'worker' => $profile->reference])
            ->log('invitation.sent');

        Notify::user($profile->user, NotificationEvent::JobInvitation, ['job' => $job->title, 'org' => $organization->display_name], route('worker.applications.index', [], false));

        return $invitation;
    }
}
