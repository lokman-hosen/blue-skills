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
    public function handle(User $member, StaffRole|string $role, User $actor): void
    {
        $role = $role instanceof StaffRole ? $role->value : $role;
        $previous = $this->staff->nameOf($member);

        $superuser = StaffRole::Superuser->value;

        if ($previous === $superuser && $role !== $superuser && $this->staff->superuserCount() <= 1) {
            throw ValidationException::withMessages(['role' => __('staff.last_superuser')]);
        }

        PlatformTeam::run(fn () => $member->unsetRelation('roles')->syncRoles([$role]));
        $member->unsetRelation('roles');

        activity('identity')->performedOn($member)->causedBy($actor)
            ->withProperties(['old' => $previous, 'new' => $role])
            ->log('staff.role_changed');
    }
}
