<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * "BLUESKILLS may contact me about my profile" (D-095). Turning it off marks the worker's lead
 * do-not-contact and clears the next follow-up; turning it back on returns the lead to "new".
 * The worker's choice wins over an agent's.
 */
class SetStaffContactPreference
{
    public function handle(WorkerProfile $profile, bool $allowed): void
    {
        if ($allowed === ($profile->staff_contact_opted_out_at === null)) {
            return;
        }

        DB::transaction(function () use ($profile, $allowed): void {
            $profile->update(['staff_contact_opted_out_at' => $allowed ? null : now()]);
            $lead = $profile->user->lead()->first();

            if ($lead !== null && ! $allowed) {
                $lead->update(['follow_up_status' => FollowUpStatus::DoNotContact, 'next_follow_up_at' => null]);
            } elseif ($lead !== null && $lead->follow_up_status === FollowUpStatus::DoNotContact) {
                $lead->update(['follow_up_status' => FollowUpStatus::New]);
            }

            activity('crm')->performedOn($profile)->causedBy($profile->user)
                ->log($allowed ? 'worker.staff_contact_allowed' : 'worker.staff_contact_opted_out');
        });
    }
}
