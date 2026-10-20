<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;

class OrganizationPolicy
{
    /**
     * Members may switch their workspace to an organization they belong to.
     */
    public function switchTo(User $user, Organization $organization): bool
    {
        return $user->memberships()->where('organization_id', $organization->id)->exists();
    }
}
