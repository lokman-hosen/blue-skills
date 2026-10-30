<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateRoleDetails
{
    public function __construct(
        private readonly RoleDirectory $roles,
        private readonly CreateRole $create,
    ) {}

    /**
     * Renames a role the Superuser created and changes its description (D-113).
     *
     * @throws ValidationException
     */
    public function handle(Role $role, string $label, ?string $description, User $actor): void
    {
        $kind = $this->roles->kindOf($role->name);

        if ($kind === null || $this->roles->isSystem($role->name)) {
            throw ValidationException::withMessages(['label' => __('roles.errors.system')]);
        }

        $this->create->ensureUnique($kind, $label, except: $role->name);

        $before = ['label' => $role->getAttribute('label'), 'description' => $role->getAttribute('description')];
        $role->forceFill(['label' => $label, 'description' => $description])->save();
        $this->roles->forget();

        activity('identity')->causedBy($actor)
            ->withProperties(['role' => $role->name, 'old' => $before, 'attributes' => ['label' => $label, 'description' => $description]])
            ->log('role.renamed');
    }
}
