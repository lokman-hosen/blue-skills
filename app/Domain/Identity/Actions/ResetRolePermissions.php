<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PermissionCatalog;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ResetRolePermissions
{
    public function __construct(
        private readonly PermissionCatalog $catalog,
        private readonly UpdateRolePermissions $update,
    ) {}

    /**
     * Puts the permissions the editor offers back to the role's defaults from code (D-112).
     *
     * @throws ValidationException
     */
    public function handle(Role $role, User $actor): void
    {
        $kind = RoleKind::of($role->name);
        $offered = $kind !== null ? $this->catalog->forKind($kind) : [];

        $this->update->handle(
            $role,
            array_values(array_intersect($offered, $this->catalog->defaults($role->name))),
            $actor,
            'role.permissions_reset',
        );
    }
}
