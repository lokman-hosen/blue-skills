<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Enums\StaffRole;

/**
 * The permissions the Superuser can grant, read from config/permissions.php (D-112): which fit
 * each kind of role, the defaults from code, and which are locked for a role and why.
 */
class PermissionCatalog
{
    public function __construct(private readonly RoleDirectory $roles) {}

    /**
     * The editor's structure for one kind of role: groups -> modules -> permission names.
     *
     * @return list<array{group: string, modules: list<array{module: string, permissions: list<string>}>}>
     */
    public function groups(RoleKind $kind): array
    {
        $groups = [];

        foreach (config('permissions.groups') as $group => $definition) {
            if (! in_array($kind->value, $definition['kinds'], true)) {
                continue;
            }

            $modules = [];

            foreach ($definition['modules'] as $module => $actions) {
                $permissions = array_values(array_filter(
                    array_map(fn (string $action) => "{$module}.{$action}", $actions),
                    fn (string $permission) => $this->fits($permission, $kind),
                ));

                if ($permissions !== []) {
                    $modules[] = ['module' => $module, 'permissions' => $permissions];
                }
            }

            if ($modules !== []) {
                $groups[] = ['group' => $group, 'modules' => $modules];
            }
        }

        return $groups;
    }

    /**
     * Every permission name the editor offers for this kind of role.
     *
     * @return list<string>
     */
    public function forKind(RoleKind $kind): array
    {
        $names = [];

        foreach ($this->groups($kind) as $group) {
            foreach ($group['modules'] as $module) {
                array_push($names, ...$module['permissions']);
            }
        }

        return $names;
    }

    /**
     * Every permission listed in the config, for any kind.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_values(array_unique(array_merge(...array_map(fn (RoleKind $kind) => $this->forKind($kind), RoleKind::cases()))));
    }

    /**
     * The role's permissions as set in code; a role the Superuser created has none.
     *
     * @return list<string>
     */
    public function defaults(string $roleName): array
    {
        $role = StaffRole::tryFrom($roleName) ?? OrganizationRole::tryFrom($roleName);

        return $role === null ? [] : array_map(fn (Permission $permission) => $permission->value, $role->permissions());
    }

    /**
     * Why a permission can't be changed for this role, as a `roles.locked.*` translation key, or
     * null when the Superuser may change it. `$on` tells which way it is locked.
     *
     * @return array{reason: string, on: bool}|null
     */
    public function lock(string $roleName, string $permission): ?array
    {
        if ($roleName === StaffRole::Superuser->value) {
            return ['reason' => 'roles.locked.superuser', 'on' => true];
        }

        if ($permission === config('permissions.staff_access_permission') && $this->roles->kindOf($roleName) === RoleKind::Staff) {
            return ['reason' => 'roles.locked.staff_access', 'on' => true];
        }

        if (in_array($permission, config('permissions.superuser_only'), true)) {
            return ['reason' => 'roles.locked.superuser_only', 'on' => false];
        }

        if (in_array($permission, config('permissions.owner_locked'), true) && OrganizationRole::tryFrom($roleName)?->isOwner() === true) {
            return ['reason' => 'roles.locked.owner', 'on' => true];
        }

        return null;
    }

    private function fits(string $permission, RoleKind $kind): bool
    {
        // Permission names contain dots, so they can't be read with config()'s dot notation.
        $only = config('permissions.only')[$permission] ?? null;

        return ! is_array($only) || in_array($kind->value, $only, true);
    }
}
