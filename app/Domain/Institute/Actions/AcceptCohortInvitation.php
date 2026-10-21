<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Institute\Enums\CohortMemberSource;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The worker accepts a cohort invitation (product rule 11). A roster line with their phone is
 * matched now, not before (D-082). The institute then sees their name and Skill Passport number,
 * and their applications only if they chose to share progress.
 */
class AcceptCohortInvitation
{
    public function handle(Cohort $cohort, WorkerProfile $profile, bool $sharesProgress): CohortMember
    {
        $existing = $cohort->members()->where('worker_profile_id', $profile->id)->first();

        if ($existing?->status === CohortMemberStatus::Accepted) {
            return $existing;
        }

        if (! $cohort->isOpen()) {
            throw ValidationException::withMessages(['cohort' => __('cohorts.invite.closed')]);
        }

        $member = DB::transaction(function () use ($cohort, $profile, $sharesProgress, $existing): CohortMember {
            $member = $existing
                ?? $cohort->members()->whereNull('worker_profile_id')->where('roster_phone', $profile->user->phone)->first()
                ?? new CohortMember(['cohort_id' => $cohort->id, 'source' => CohortMemberSource::Link]);

            $member->fill([
                'worker_profile_id' => $profile->id,
                'status' => CohortMemberStatus::Accepted,
                'shares_progress' => $sharesProgress,
                'responded_at' => now(),
                'left_at' => null,
            ])->save();

            activity('institute')->performedOn($member)->causedBy($profile->user)
                ->withProperties(['cohort' => $cohort->id, 'shares_progress' => $sharesProgress, 'source' => $member->source->value])
                ->log('cohort_member.accepted');

            return $member;
        });

        foreach (Notify::members($cohort->organization, Permission::GraduatesInvite) as $recipient) {
            Notify::user($recipient, NotificationEvent::GraduateJoined, [
                'worker' => (string) $profile->user->name,
                'cohort' => $cohort->name,
                'course' => $cohort->course->title,
            ], route('org.cohorts.show', $cohort, false));
        }

        return $member;
    }
}
