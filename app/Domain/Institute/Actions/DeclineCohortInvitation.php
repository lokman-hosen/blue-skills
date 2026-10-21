<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Worker\Models\WorkerProfile;

/**
 * The worker says no. Only a roster line with their phone records it (as declined, without
 * linking the profile); otherwise nothing about them reaches the institute.
 */
class DeclineCohortInvitation
{
    public function handle(Cohort $cohort, WorkerProfile $profile): void
    {
        $line = $cohort->members()
            ->whereNull('worker_profile_id')
            ->where('status', CohortMemberStatus::Invited)
            ->where('roster_phone', $profile->user->phone)
            ->first();

        $line?->update(['status' => CohortMemberStatus::Declined, 'responded_at' => now()]);
    }
}
