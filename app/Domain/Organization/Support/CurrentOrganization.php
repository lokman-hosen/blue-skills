<?php

namespace App\Domain\Organization\Support;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use Illuminate\Contracts\Session\Session;

/**
 * The organization workspace a member is acting in. Stored in the session; defaults to the
 * user's first membership.
 */
class CurrentOrganization
{
    public const SESSION_KEY = 'current_organization_id';

    public function resolve(User $user, Session $session): ?Organization
    {
        // Query fresh: the relation may be cached from before a registration or switch.
        $organizations = $user->organizations()->orderBy('organization_members.joined_at')->get();
        $id = $session->get(self::SESSION_KEY);

        return $organizations->firstWhere('id', $id) ?? $organizations->first();
    }
}
