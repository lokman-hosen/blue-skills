<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Assign a lead, set its follow-up status and the next follow-up date. A worker who turned off
 * calls from BLUESKILLS stays do-not-contact until they turn them back on (D-095).
 */
class UpdateLeadFollowUp
{
    public function handle(Lead $lead, ?string $assignedTo, FollowUpStatus $status, ?CarbonInterface $nextFollowUpAt, User $agent): void
    {
        $optedOut = $lead->user?->workerProfile?->staff_contact_opted_out_at !== null;

        if ($optedOut && $status !== FollowUpStatus::DoNotContact) {
            throw ValidationException::withMessages(['follow_up_status' => __('crm.errors.opted_out')]);
        }

        $closed = in_array($status, [FollowUpStatus::DoNotContact, FollowUpStatus::NotInterested], true);

        $lead->update([
            'assigned_to' => $assignedTo,
            'follow_up_status' => $status,
            'next_follow_up_at' => $closed ? null : $nextFollowUpAt,
        ]);
    }
}
