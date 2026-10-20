<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\Permission as PermissionName;
use App\Domain\Identity\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles are global definitions (organization_id = null). Organization roles are assigned
 * per organization team; staff roles under PlatformTeam::ID.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId(null);

        foreach (PermissionName::cases() as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
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
            $role->syncPermissions(array_map(fn (PermissionName $permission) => $permission->value, $permissions));
        }

        $registrar->forgetCachedPermissions();
    }
}
