<?php

namespace App\Domain\Manual\Support;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\RoleDirectory;
use App\Domain\Identity\Support\StaffDirectory;
use App\Domain\Manual\Enums\ManualRole;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationRoles;

/**
 * Which manuals a user may read (D-110): a worker reads the worker manual, an organization
 * member the manual of each role they hold in any of their organizations, a staff member the
 * manual of their staff role. Superusers read every manual.
 */
class ManualRoles
{
    public function __construct(
        private readonly OrganizationRoles $organizationRoles,
        private readonly StaffDirectory $staff,
        private readonly RoleDirectory $directory,
    ) {}

    /**
     * The roles the user holds, in reading order. Someone in a role the Superuser created reads
     * the manual of the built-in role it started from (D-113).
     *
     * @return list<ManualRole>
     */
    public function heldBy(User $user): array
    {
        $held = array_unique(array_map(
            fn (string $name) => $this->directory->basedOn($name) ?? $name,
            $this->roleNames($user),
        ));

        return array_values(array_filter(ManualRole::cases(), fn (ManualRole $role) => in_array($role->value, $held, true)));
    }

    /**
     * When the user reads this manual only because of a created role, that role's name; the page
     * then says their role was adjusted and some steps may not apply.
     */
    public function adaptedRole(User $user, ManualRole $manual): ?string
    {
        $names = $this->roleNames($user);

        if (in_array($manual->value, $names, true)) {
            return null;
        }

        foreach ($names as $name) {
            if ($this->directory->basedOn($name) === $manual->value) {
                return $this->directory->label($name);
            }
        }

        return null;
    }

    /**
     * Every role name the user holds: worker, their staff role, or their role in each organization.
     *
     * @return list<string>
     */
    private function roleNames(User $user): array
    {
        if ($user->isWorker()) {
            return [ManualRole::Worker->value];
        }

        if ($user->isStaff()) {
            $name = $this->staff->nameOf($user);

            return $name !== null ? [$name] : [];
        }

        return $user->organizations
            ->map(fn (Organization $organization) => $this->organizationRoles->nameOf($user, $organization))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function canReadAll(User $user): bool
    {
        return $user->hasStaffRole(StaffRole::Superuser);
    }

    /**
     * Every manual the user may open, in reading order.
     *
     * @return list<ManualRole>
     */
    public function readableBy(User $user): array
    {
        return $this->canReadAll($user) ? ManualRole::cases() : $this->heldBy($user);
    }

    public function canRead(User $user, ManualRole $role): bool
    {
        return in_array($role, $this->readableBy($user), true);
    }
}
