<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\PlatformTeam;
use App\Domain\Identity\Support\StaffDirectory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeactivateStaffMember
{
    public function __construct(private readonly StaffDirectory $staff) {}

    /**
     * Removes staff roles and soft-deletes the account, which then cannot sign in (D-037).
     *
     * @throws ValidationException
     */
    public function handle(User $member, User $actor): void
    {
        if ($member->is($actor)) {
            throw ValidationException::withMessages(['member' => __('staff.cannot_deactivate_self')]);
        }

        if ($this->staff->roleOf($member) === StaffRole::Superuser && $this->staff->superuserCount() <= 1) {
            throw ValidationException::withMessages(['member' => __('staff.last_superuser')]);
        }

        DB::transaction(function () use ($member, $actor): void {
            PlatformTeam::run(fn () => $member->unsetRelation('roles')->syncRoles([]));
            $member->delete();

            activity('identity')->performedOn($member)->causedBy($actor)->log('staff.deactivated');
        });
    }
}
