<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\RoleDirectory;
use App\Domain\Identity\Support\RolePermissionOverview;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DeleteRole
{
    public function __construct(
        private readonly RoleDirectory $roles,
        private readonly RolePermissionOverview $overview,
    ) {}

    /**
     * Deletes a role the Superuser created, once nobody holds it and no invitation would give it
     * to someone (D-113).
     *
     * @throws ValidationException
     */
    public function handle(Role $role, User $actor): void
    {
        if ($this->roles->isSystem($role->name)) {
            throw ValidationException::withMessages(['role' => __('roles.errors.system')]);
        }

        if (($members = $this->overview->membersOf($role)) > 0) {
            throw ValidationException::withMessages(['role' => __('roles.errors.in_use', ['count' => $members])]);
        }

        if (($invitations = $this->overview->pendingInvitations($role->name)) > 0) {
            throw ValidationException::withMessages(['role' => __('roles.errors.invited', ['count' => $invitations])]);
        }

        DB::transaction(function () use ($role, $actor): void {
            $snapshot = [
                'label' => $role->getAttribute('label'),
                'kind' => $role->getAttribute('kind'),
                'permissions' => $role->permissions->pluck('name')->all(),
            ];
            $role->delete();

            activity('identity')->causedBy($actor)
                ->withProperties(['role' => $role->name, 'old' => $snapshot])
                ->log('role.deleted');
        });

        $this->roles->forget();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
