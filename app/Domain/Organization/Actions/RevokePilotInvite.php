<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\PilotInvite;

/**
 * Stops a pilot code working (D-108). Organizations that already registered with it keep
 * their registration.
 */
class RevokePilotInvite
{
    public function handle(PilotInvite $invite, User $actor): void
    {
        if ($invite->revoked_at !== null) {
            return;
        }

        $invite->update(['revoked_at' => now()]);

        activity('organization')->performedOn($invite)->causedBy($actor)->log('pilot_invite.revoked');
    }
}
