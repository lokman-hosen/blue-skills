<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PermissionCatalog;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ResetRolePermissions
{
    public function __construct(
        private readonly PermissionCatalog $catalog,
        private readonly UpdateRolePermissions $update,
        private readonly RoleDirectory $roles,
    ) {}

    /**
     * Puts the permissions the editor offers back to the role's defaults from code (D-112).
     *
     * @throws ValidationException
     */
    public function handle(Role $role, User $actor): void
    {
        // A role the Superuser created has no defaults to go back to (D-113).
        if (! $this->roles->isSystem($role->name)) {
            throw ValidationException::withMessages(['permissions' => __('roles.errors.no_defaults')]);
        }

        $kind = $this->roles->kindOf($role->name);
        $offered = $kind !== null ? $this->catalog->forKind($kind) : [];

        $this->update->handle(
            $role,
            array_values(array_intersect($offered, $this->catalog->defaults($role->name))),
            $actor,
            'role.permissions_reset',
        );
    }
}
