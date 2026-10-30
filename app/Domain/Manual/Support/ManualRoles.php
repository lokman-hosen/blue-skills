<?php

namespace App\Domain\Manual\Support;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
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
    ) {}

    /**
     * The roles the user holds, in reading order.
     *
     * @return list<ManualRole>
     */
    public function heldBy(User $user): array
    {
        if ($user->isWorker()) {
            return [ManualRole::Worker];
        }

        if ($user->isStaff()) {
            $role = $this->staff->roleOf($user);

            return $role !== null ? [ManualRole::fromStaffRole($role)] : [];
        }

        $held = $user->organizations
            ->map(fn (Organization $organization) => $this->organizationRoles->roleOf($user, $organization))
            ->filter()
            ->map(fn ($role) => ManualRole::fromOrganizationRole($role)->value)
            ->unique()
            ->all();

        return array_values(array_filter(ManualRole::cases(), fn (ManualRole $role) => in_array($role->value, $held, true)));
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
