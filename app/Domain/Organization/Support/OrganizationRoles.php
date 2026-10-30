<?php

namespace App\Domain\Organization\Support;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Reads and writes a member's role inside one organization (spatie team = organization).
 */
class OrganizationRoles
{
    public function roleOf(User $user, Organization $organization): ?OrganizationRole
    {
        $name = TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->getRoleNames()->first());

        return is_string($name) ? OrganizationRole::tryFrom($name) : null;
    }

    /** The member's role name, built-in or created by the Superuser (D-113). */
    public function nameOf(User $user, Organization $organization): ?string
    {
        $name = TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->getRoleNames()->first());
        $user->unsetRelation('roles');

        return is_string($name) ? $name : null;
    }

    public function assign(User $user, Organization $organization, OrganizationRole|string $role): void
    {
        $name = $role instanceof OrganizationRole ? $role->value : $role;
        TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->syncRoles([$name]));
        $user->unsetRelation('roles');
    }

    public function revokeAll(User $user, Organization $organization): void
    {
        TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->syncRoles([]));
        $user->unsetRelation('roles');
    }

    public function ownerCount(Organization $organization): int
    {
        $owners = array_map(
            fn (OrganizationRole $role) => $role->value,
            array_filter(OrganizationRole::cases(), fn (OrganizationRole $role) => $role->isOwner()),
        );

        return DB::table(config('permission.table_names.model_has_roles'))
            ->join(config('permission.table_names.roles'), 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.'.config('permission.column_names.team_foreign_key'), $organization->id)
            ->where('model_has_roles.model_type', 'user')
            ->whereIn('roles.name', $owners)
            ->distinct()
            ->count('model_has_roles.'.config('permission.column_names.model_morph_key'));
    }

    public function isLastOwner(User $user, Organization $organization): bool
    {
        return $this->roleOf($user, $organization)?->isOwner() === true && $this->ownerCount($organization) <= 1;
    }
}
