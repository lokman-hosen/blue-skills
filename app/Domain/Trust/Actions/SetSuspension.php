<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Suspended as JobSuspended;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Suspended as OrganizationSuspended;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\TrustAction;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\TrustCase\ActionTaken;
use App\Domain\Trust\States\TrustCase\Investigating;
use App\Domain\Trust\States\TrustCase\Open;
use App\Domain\Trust\Support\TrustSubject;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Suspends or restores an organization, job or worker with a reason code (spec §10, D-088).
 *
 * - A job leaves search; its applicants are told.
 * - An organization's live and paused jobs are suspended with it and its members can only read
 *   and appeal; restoring brings back the jobs it suspended (not jobs suspended on their own).
 * - A worker is hidden from talent search and pools and can't apply, but can sign in and appeal.
 *
 * Applications and mobility cases that already exist carry on. Every change is a trust action.
 */
class SetSuspension
{
    public function handle(Organization|JobPosting|WorkerProfile $subject, TrustActionType $type, TrustReason $reason, ?string $note, User $actor, ?TrustCase $case = null): TrustAction
    {
        $suspend = $type === TrustActionType::Suspend;

        if (TrustSubject::isSuspended($subject) === $suspend) {
            throw ValidationException::withMessages(['action' => __($suspend ? 'trust.errors.already_suspended' : 'trust.errors.not_suspended')]);
        }

        $action = DB::transaction(function () use ($subject, $type, $suspend, $reason, $note, $actor, $case): TrustAction {
            match (true) {
                $subject instanceof JobPosting => $this->job($subject, $suspend),
                $subject instanceof Organization => $this->organization($subject, $suspend),
                $subject instanceof WorkerProfile => $subject->update(['suspended_at' => $suspend ? now() : null]),
            };

            $action = TrustAction::query()->create([
                'trust_case_id' => $case?->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'action' => $type,
                'reason_code' => $reason,
                'note' => $note,
                'actor_id' => $actor->id,
            ]);

            if ($case !== null && $suspend) {
                if ($case->status->equals(Open::class)) {
                    $case->status->transitionTo(Investigating::class);
                }

                if ($case->status->equals(Investigating::class)) {
                    $case->status->transitionTo(ActionTaken::class);
                }
            }

            activity('trust')->performedOn($subject)->causedBy($actor)
                ->withProperties(['action' => $type->value, 'reason' => $reason->value, 'note' => $note, 'case' => $case?->reference])
                ->log("trust.{$type->value}");

            return $action;
        });

        $this->notify($subject, $suspend, $reason);

        return $action;
    }

    private function job(JobPosting $job, bool $suspend): void
    {
        if ($suspend) {
            if (! $job->status->equals(Published::class, Paused::class)) {
                throw ValidationException::withMessages(['action' => __('trust.errors.job_not_live')]);
            }

            $job->status->transitionTo(JobSuspended::class);
            $this->tellApplicants($job);

            return;
        }

        $job->status->transitionTo(Published::class);
    }

    private function organization(Organization $organization, bool $suspend): void
    {
        if ($suspend) {
            if (! $organization->verification_status->equals(Verified::class)) {
                throw ValidationException::withMessages(['action' => __('trust.errors.organization_not_verified')]);
            }

            $organization->verification_status->transitionTo(OrganizationSuspended::class);
            $organization->postedJobs()->whereState('status', [Published::class, Paused::class])->get()
                ->each(function (JobPosting $job): void {
                    $job->status->transitionTo(JobSuspended::class);
                    $this->tellApplicants($job);
                });

            return;
        }

        $organization->verification_status->transitionTo(Verified::class);

        // Jobs suspended with the organization come back; jobs suspended on their own stay.
        $organization->postedJobs()->whereState('status', JobSuspended::class)->get()
            ->reject(fn (JobPosting $job) => self::suspendedOnItsOwn($job))
            ->each(fn (JobPosting $job) => $job->status->transitionTo(Published::class));
    }

    public static function suspendedOnItsOwn(JobPosting $job): bool
    {
        return TrustAction::query()
            ->where('subject_type', $job->getMorphClass())->where('subject_id', $job->id)
            ->latest()->orderByDesc('id')->first()?->action === TrustActionType::Suspend;
    }

    private function tellApplicants(JobPosting $job): void
    {
        $job->applications()->whereNotState('status', ['rejected', 'withdrawn', 'declined'])
            ->with(['workerProfile.user', 'job.organization'])->get()
            ->each(fn (Application $application) => Notify::worker($application, NotificationEvent::JobSuspended, [], route('worker.applications.show', $application, false)));
    }

    private function notify(Organization|JobPosting|WorkerProfile $subject, bool $suspend, TrustReason $reason): void
    {
        $params = ['subject' => TrustSubject::label($subject), 'suspension' => $suspend ? 'suspended' : 'restored', 'trust_reason' => $reason->value];

        if ($subject instanceof WorkerProfile) {
            if ($subject->user !== null) {
                Notify::user($subject->user, NotificationEvent::AccountSuspended, $params, route('appeals.index', absolute: false));
            }

            return;
        }

        $organization = $subject instanceof JobPosting ? $subject->organization : $subject;

        foreach (Notify::members($organization, Permission::JobsView) as $member) {
            Notify::user($member, NotificationEvent::OrganizationSuspended, $params, route('appeals.index', absolute: false));
        }
    }
}
