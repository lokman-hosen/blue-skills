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
        $organizations = $user->organizations;
        $id = $session->get(self::SESSION_KEY);

        return $organizations->firstWhere('id', $id) ?? $organizations->first();
    }
}
