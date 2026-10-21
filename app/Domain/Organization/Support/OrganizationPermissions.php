<?php

namespace App\Domain\Organization\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;

/**
 * Whether a user holds a permission in an organization (spatie teams, D-004). Never throws
 * for a missing permission (D-045).
 */
final class OrganizationPermissions
{
    public static function isMember(User $user, Organization $organization): bool
    {
        return $user->memberships()->where('organization_id', $organization->id)->exists();
    }

    public static function allows(User $user, Organization $organization, Permission $permission): bool
    {
        if (! self::isMember($user, $organization)) {
            return false;
        }

        return TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->unsetRelation('permissions')
            ->checkPermissionTo($permission->value));
    }
}
