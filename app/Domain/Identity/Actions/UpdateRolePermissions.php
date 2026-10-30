<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PermissionCatalog;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateRolePermissions
{
    public function __construct(
        private readonly PermissionCatalog $catalog,
        private readonly RoleDirectory $roles,
    ) {}

    /**
     * Sets which of the permissions the editor offers this role holds (D-112). Permissions the
     * editor doesn't offer for the role are left as they are. Takes effect on the next request.
     *
     * @param  list<string>  $granted
     *
     * @throws ValidationException
     */
    public function handle(Role $role, array $granted, User $actor, string $event = 'role.permissions_updated'): void
    {
        $kind = $this->roles->kindOf($role->name);
        $offered = $kind !== null ? $this->catalog->forKind($kind) : [];

        // The Superuser always holds every staff permission and is never edited.
        if ($kind === null || $role->name === StaffRole::Superuser->value) {
            throw ValidationException::withMessages(['permissions' => __('roles.errors.not_editable')]);
        }

        if (($unknown = array_diff($granted, $offered)) !== []) {
            throw ValidationException::withMessages(['permissions' => __('roles.errors.not_for_role', ['permissions' => implode(', ', $unknown)])]);
        }

        foreach ($offered as $permission) {
            $lock = $this->catalog->lock($role->name, $permission);

            if ($lock !== null && in_array($permission, $granted, true) !== $lock['on']) {
                throw ValidationException::withMessages(['permissions' => __('roles.errors.locked', ['permission' => __("permission.{$permission}")])]);
            }
        }

        DB::transaction(function () use ($role, $granted, $offered, $actor, $event): void {
            $before = $role->permissions()->pluck('name')->all();
            $after = array_values(array_unique([...array_diff($before, $offered), ...$granted]));

            $role->syncPermissions($after);

            activity('identity')->causedBy($actor)
                ->withProperties([
                    'role' => $role->name,
                    'old' => ['permissions' => array_values(array_intersect($offered, $before))],
                    'attributes' => ['permissions' => array_values(array_intersect($offered, $granted))],
                ])
                ->log($event);
        });
    }
}
