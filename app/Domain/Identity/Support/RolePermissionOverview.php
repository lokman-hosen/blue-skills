<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Data\RoleGroupData;
use App\Domain\Identity\Data\RoleModuleData;
use App\Domain\Identity\Data\RolePermissionData;
use App\Domain\Identity\Data\RoleSummaryData;
use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Enums\StaffRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * What Admin › Roles & permissions shows (D-112): every role with its people and permissions,
 * and one role's permissions grouped for the editor.
 */
class RolePermissionOverview
{
    public function __construct(private readonly PermissionCatalog $catalog) {}

    /**
     * @return list<RoleSummaryData>
     */
    public function summaries(): array
    {
        $roles = $this->globalRoles()->with('permissions')->get()->keyBy('name');
        $members = $this->memberCounts();
        $summaries = [];

        foreach (RoleKind::cases() as $kind) {
            foreach ($kind->roleNames() as $name) {
                $role = $roles->get($name);

                if ($role instanceof Role) {
                    $summaries[] = $this->summary($role, $kind, $members[$role->id] ?? 0);
                }
            }
        }

        return $summaries;
    }

    /**
     * @return list<RoleGroupData>
     */
    public function groups(Role $role): array
    {
        $kind = RoleKind::of($role->name);

        if ($kind === null) {
            return [];
        }

        $held = $role->permissions->pluck('name')->all();
        $defaults = $this->catalog->defaults($role->name);

        return array_map(fn (array $group) => new RoleGroupData(
            key: $group['group'],
            label: __("permission_group.{$group['group']}"),
            modules: array_map(fn (array $module) => new RoleModuleData(
                key: $module['module'],
                label: __("permission_module.{$module['module']}"),
                permissions: array_map(function (string $permission) use ($role, $held, $defaults): RolePermissionData {
                    $lock = $this->catalog->lock($role->name, $permission);

                    return new RolePermissionData(
                        name: $permission,
                        label: __("permission.{$permission}"),
                        hint: __("permission_hint.{$permission}"),
                        granted: $lock !== null ? $lock['on'] : in_array($permission, $held, true),
                        default: in_array($permission, $defaults, true),
                        lockedReason: $lock !== null ? __($lock['reason']) : null,
                    );
                }, $module['permissions']),
            ), $group['modules']),
        ), $this->catalog->groups($kind));
    }

    public function summaryOf(Role $role): RoleSummaryData
    {
        $kind = RoleKind::of($role->name);
        abort_if($kind === null, 404);

        return $this->summary($role, $kind, $this->memberCounts()[$role->id] ?? 0);
    }

    /**
     * The global role with this name, or a 404 for anything that isn't one of BLUESKILLS' roles.
     */
    public function find(string $name): Role
    {
        abort_if(RoleKind::of($name) === null, 404);

        $role = $this->globalRoles()->where('name', $name)->with('permissions')->first();
        abort_unless($role instanceof Role, 404);

        return $role;
    }

    private function summary(Role $role, RoleKind $kind, int $members): RoleSummaryData
    {
        $offered = $this->catalog->forKind($kind);
        $held = array_values(array_intersect($offered, $role->permissions->pluck('name')->all()));
        $defaults = array_values(array_intersect($offered, $this->catalog->defaults($role->name)));
        sort($held);
        sort($defaults);

        return new RoleSummaryData(
            name: $role->name,
            label: __("role.{$role->name}"),
            kind: $kind->value,
            members: $members,
            granted: count($held),
            offered: count($offered),
            changed: $held !== $defaults,
            locked: $role->name === StaffRole::Superuser->value,
        );
    }

    /**
     * @return Builder<Role>
     */
    private function globalRoles(): Builder
    {
        return Role::query()->where('guard_name', 'web')->whereNull(config('permission.column_names.team_foreign_key'));
    }

    /**
     * People per role id, across every organization and the platform team; deactivated accounts
     * are left out.
     *
     * @return array<string|int, int>
     */
    private function memberCounts(): array
    {
        $table = config('permission.table_names.model_has_roles');
        $key = config('permission.column_names.model_morph_key');

        /** @var Collection<int, object{role_id: string|int, members: int}> $rows */
        $rows = DB::table($table)
            ->join('users', 'users.id', '=', "{$table}.{$key}")
            ->where("{$table}.model_type", 'user')
            ->whereNull('users.deleted_at')
            ->groupBy("{$table}.role_id")
            ->select("{$table}.role_id", DB::raw("count(distinct {$table}.{$key}) as members"))
            ->get();

        return $rows->mapWithKeys(fn (object $row) => [$row->role_id => (int) $row->members])->all();
    }
}
