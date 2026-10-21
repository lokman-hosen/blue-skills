<?php

namespace App\Domain\Applications\Policies;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

class ShortlistPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::ShortlistsManage);
    }

    public function create(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::ShortlistsManage);
    }

    /**
     * View, rename, delete and change entries.
     */
    public function manage(User $user, Shortlist $shortlist): bool
    {
        return OrganizationPermissions::allows($user, $shortlist->organization, Permission::ShortlistsManage);
    }
}
