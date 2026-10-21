<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\JobInvitation;
use Illuminate\Validation\ValidationException;

/**
 * "Not interested". The organization sees the answer; nothing else is shared.
 */
class DeclineInvitation
{
    public function handle(JobInvitation $invitation): void
    {
        if ($invitation->status !== InvitationStatus::Sent) {
            throw ValidationException::withMessages(['invitation' => __('invitations.already_answered')]);
        }

        $invitation->update(['status' => InvitationStatus::Declined, 'responded_at' => now()]);
    }
}
