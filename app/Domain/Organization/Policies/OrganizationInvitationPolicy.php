<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\OrganizationInvitation;
use Illuminate\Support\Str;

class OrganizationInvitationPolicy
{
    /**
     * Only the invited address (verified by the `verified` middleware) may accept.
     */
    public function accept(User $user, OrganizationInvitation $invitation): bool
    {
        return $user->type === UserType::Org
            && $invitation->isPending()
            && $user->email !== null
            && Str::lower($user->email) === Str::lower((string) $invitation->email);
    }

    public function revoke(User $user, OrganizationInvitation $invitation): bool
    {
        return $invitation->isPending() && (new OrganizationPolicy)->manageTeam($user, $invitation->organization);
    }
}
