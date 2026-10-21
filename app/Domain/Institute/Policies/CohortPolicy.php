<?php

namespace App\Domain\Institute\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Cohorts belong to their institute. `cohorts.manage` edits and closes them; `graduates.invite`
 * shares the invite link and keeps the roster (D-082).
 */
class CohortPolicy
{
    public function view(User $user, Cohort $cohort): bool
    {
        return (new CoursePolicy)->viewAny($user, $cohort->organization);
    }

    public function create(User $user, Cohort $cohort): bool
    {
        return $this->update($user, $cohort);
    }

    public function update(User $user, Cohort $cohort): bool
    {
        return OrganizationPermissions::allows($user, $cohort->organization, Permission::CohortsManage);
    }

    public function invite(User $user, Cohort $cohort): bool
    {
        return $cohort->isOpen() && OrganizationPermissions::allows($user, $cohort->organization, Permission::GraduatesInvite);
    }
}
