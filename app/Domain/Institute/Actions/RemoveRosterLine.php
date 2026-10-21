<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\CohortMember;
use Illuminate\Validation\ValidationException;

/**
 * A roster line nobody has answered can be removed. Graduates who answered stay; only they can
 * leave (product rule 11).
 */
class RemoveRosterLine
{
    public function handle(CohortMember $member, User $actor): void
    {
        if ($member->status !== CohortMemberStatus::Invited || $member->worker_profile_id !== null) {
            throw ValidationException::withMessages(['member' => __('cohorts.roster.not_removable')]);
        }

        activity('institute')->performedOn($member->cohort)->causedBy($actor)
            ->withProperties(['member' => $member->id])
            ->log('cohort.roster_removed');

        $member->delete();
    }
}
