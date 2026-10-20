<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
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

    /**
     * Organization users (not workers or staff) may register an organization.
     */
    public function create(User $user): bool
    {
        return $user->type === UserType::Org;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->hasPermission($user, $organization, Permission::OrganizationUpdate);
    }

    public function viewTeam(User $user, Organization $organization): bool
    {
        return $this->switchTo($user, $organization);
    }

    public function manageTeam(User $user, Organization $organization): bool
    {
        return $this->hasPermission($user, $organization, Permission::TeamManage);
    }

    private function hasPermission(User $user, Organization $organization, Permission $permission): bool
    {
        if (! $this->switchTo($user, $organization)) {
            return false;
        }

        return TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->unsetRelation('permissions')
            ->checkPermissionTo($permission->value));
    }
}
