<?php

namespace App\Domain\Jobs\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Agencies request mandates; the named employer confirms them; staff verify them (D-074,
 * product rule 10).
 */
class JobMandatePolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return match ($organization->type) {
            OrganizationType::Agency => OrganizationPermissions::allows($user, $organization, Permission::MandatesManage)
                || OrganizationPermissions::allows($user, $organization, Permission::ComplianceReview),
            OrganizationType::Employer => OrganizationPermissions::allows($user, $organization, Permission::MandatesConfirm),
            default => false,
        };
    }

    public function create(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Agency
            && OrganizationPermissions::allows($user, $organization, Permission::MandatesManage);
    }

    /**
     * The named employer confirms or declines while it waits for them.
     */
    public function confirm(User $user, JobMandate $mandate): bool
    {
        return $mandate->status === MandateStatus::AwaitingEmployer
            && OrganizationPermissions::allows($user, $mandate->employer, Permission::MandatesConfirm);
    }

    /**
     * Add evidence while the mandate is with staff.
     */
    public function update(User $user, JobMandate $mandate): bool
    {
        return in_array($mandate->status, [MandateStatus::AwaitingEmployer, MandateStatus::Pending], true)
            && OrganizationPermissions::allows($user, $mandate->agency, Permission::MandatesManage);
    }

    public function review(User $user, ?JobMandate $mandate = null): bool
    {
        return $user->hasStaffPermission(Permission::VerificationsReview->value);
    }
}
