<?php

namespace App\Domain\Institute\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Institutes keep pools (`talent_pools.manage`); verified employers and agencies who may search
 * talent browse the published ones (spec §8, D-082). Employer and agency shortlists are their
 * own pools.
 */
class TalentPoolPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Institute
            && OrganizationPermissions::allows($user, $organization, Permission::TalentPoolsManage);
    }

    public function update(User $user, TalentPool $pool): bool
    {
        return $this->viewAny($user, $pool->organization);
    }

    /**
     * Browsing other institutes' published pools.
     */
    public function browse(User $user, Organization $organization): bool
    {
        return in_array($organization->type, [OrganizationType::Employer, OrganizationType::Agency], true)
            && $organization->isVerified()
            && OrganizationPermissions::allows($user, $organization, Permission::TalentSearch);
    }

    public function view(User $user, TalentPool $pool, Organization $organization): bool
    {
        return $pool->isPublished() && $this->browse($user, $organization);
    }
}
