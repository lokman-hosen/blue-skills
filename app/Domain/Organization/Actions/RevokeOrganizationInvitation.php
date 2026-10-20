<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\OrganizationInvitation;

class RevokeOrganizationInvitation
{
    public function handle(OrganizationInvitation $invitation, User $actor): void
    {
        $invitation->forceFill(['revoked_at' => now()])->save();

        activity('organization')->performedOn($invitation->organization)->causedBy($actor)
            ->withProperties(['email' => $invitation->email])
            ->log('member.invitation_revoked');
    }
}
