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
        return in_array($permission->value, self::granted($user, $organization) ?? [], true);
    }

    /**
     * The member's permission names in the organization, or null for a non-member. Every page
     * checks a dozen abilities for the navigation, so an HTTP request remembers the answer
     * (D-106); queue workers and commands always ask the database, as they live for many jobs.
     *
     * @return list<string>|null
     */
    private static function granted(User $user, Organization $organization): ?array
    {
        $load = fn (): ?array => self::isMember($user, $organization)
            ? TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->unsetRelation('permissions')->load('permissions')
                ->getAllPermissions()->pluck('name')->values()->all())
            : null;

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return $load();
        }

        $attributes = request()->attributes;
        $key = "organization_permissions:{$user->id}:{$organization->id}";

        if (! $attributes->has($key)) {
            $attributes->set($key, $load());
        }

        return $attributes->get($key);
    }
}
