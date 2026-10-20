<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PlatformTeam;
use App\Domain\Identity\Support\StaffDirectory;
use Illuminate\Validation\ValidationException;

class ChangeStaffRole
{
    public function __construct(private readonly StaffDirectory $staff) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $member, StaffRole $role, User $actor): void
    {
        $previous = $this->staff->roleOf($member);

        if ($previous === StaffRole::Superuser && $role !== StaffRole::Superuser && $this->staff->superuserCount() <= 1) {
            throw ValidationException::withMessages(['role' => __('staff.last_superuser')]);
        }

        PlatformTeam::run(fn () => $member->unsetRelation('roles')->syncRoles([$role->value]));
        $member->unsetRelation('roles');

        activity('identity')->performedOn($member)->causedBy($actor)
            ->withProperties(['old' => $previous?->value, 'new' => $role->value])
            ->log('staff.role_changed');
    }
}
