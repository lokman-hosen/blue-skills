<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\Permission as PermissionName;
use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles are global definitions (organization_id = null). Organization roles are assigned
 * per organization team; staff roles under PlatformTeam::ID.
 *
 * The Superuser changes role permissions in Admin › Roles & permissions (D-112), so re-running
 * this seeder must not undo that: a role gets its defaults from code only when it is created, and
 * a permission added in code later is given once to the roles that have it by default. The
 * Superuser role always holds every staff permission.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId(null);

        $created = [];

        foreach (PermissionName::cases() as $permission) {
            if (Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web'])->wasRecentlyCreated) {
                $created[] = $permission->value;
            }
        }

        $roles = [
            ...array_map(fn (OrganizationRole $role) => [$role->value, $role->permissions()], OrganizationRole::cases()),
            ...array_map(fn (StaffRole $role) => [$role->value, $role->permissions()], StaffRole::cases()),
        ];

        foreach ($roles as [$name, $permissions]) {
            $role = Role::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
                config('permission.column_names.team_foreign_key') => null,
            ]);
            // Built-in roles: the Superuser can't rename or delete them (D-113).
            $role->forceFill(['kind' => RoleKind::of($name)?->value, 'system' => true])->save();
            $defaults = array_map(fn (PermissionName $permission) => $permission->value, $permissions);

            if ($role->wasRecentlyCreated || $name === StaffRole::Superuser->value) {
                $role->syncPermissions($defaults);
            } elseif (($new = array_values(array_intersect($defaults, $created))) !== []) {
                $role->givePermissionTo($new);
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
