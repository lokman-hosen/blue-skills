<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PermissionCatalog;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateRole
{
    public function __construct(
        private readonly RoleDirectory $roles,
        private readonly PermissionCatalog $catalog,
    ) {}

    /**
     * A new role of one kind, started from an existing role of the same kind (D-113). Its user
     * manual follows the built-in role it comes from. It can copy that role's permissions, or
     * start with none; locked permissions are applied either way.
     *
     * @throws ValidationException
     */
    public function handle(RoleKind $kind, string $label, ?string $description, string $basedOn, bool $copyPermissions, User $actor): Role
    {
        if ($this->roles->kindOf($basedOn) !== $kind) {
            throw ValidationException::withMessages(['based_on' => __('roles.errors.base')]);
        }

        $this->ensureUnique($kind, $label);

        $base = Role::findByName($basedOn, 'web');
        $name = RoleDirectory::CUSTOM_PREFIX.strtolower((string) Str::ulid());

        $role = DB::transaction(function () use ($kind, $label, $description, $basedOn, $copyPermissions, $actor, $base, $name): Role {
            /** @var Role $role */
            $role = Role::query()->forceCreate([
                'name' => $name,
                'guard_name' => 'web',
                config('permission.column_names.team_foreign_key') => null,
                'kind' => $kind->value,
                'label' => $label,
                'description' => $description,
                // The manual follows a built-in role, so a role started from a created role
                // keeps that role's own starting point.
                'based_on' => $this->roles->basedOn($basedOn) ?? $basedOn,
                'system' => false,
                'created_by' => $actor->id,
            ]);
            $this->roles->forget();

            $offered = $this->catalog->forKind($kind);
            $permissions = $copyPermissions ? array_values(array_intersect($offered, $base->permissions->pluck('name')->all())) : [];

            foreach ($offered as $permission) {
                $lock = $this->catalog->lock($name, $permission);

                if ($lock !== null) {
                    $permissions = $lock['on']
                        ? array_values(array_unique([...$permissions, $permission]))
                        : array_values(array_diff($permissions, [$permission]));
                }
            }

            $role->syncPermissions($permissions);

            activity('identity')->causedBy($actor)
                ->withProperties([
                    'role' => $name,
                    'attributes' => ['label' => $label, 'kind' => $kind->value, 'based_on' => $basedOn, 'permissions' => $permissions],
                ])
                ->log('role.created');

            return $role;
        });

        $this->roles->forget();

        return $role;
    }

    /**
     * @throws ValidationException
     */
    public function ensureUnique(RoleKind $kind, string $label, ?string $except = null): void
    {
        foreach ($this->roles->namesFor($kind) as $name) {
            if ($name !== $except && mb_strtolower(trim($this->roles->label($name))) === mb_strtolower(trim($label))) {
                throw ValidationException::withMessages(['label' => __('roles.errors.taken', ['name' => $label])]);
            }
        }
    }
}
