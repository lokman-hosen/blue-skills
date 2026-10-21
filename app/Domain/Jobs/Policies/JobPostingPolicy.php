<?php

namespace App\Domain\Jobs\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

class JobPostingPolicy
{
    /**
     * The organization's own job list.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::JobsView);
    }

    /**
     * Published jobs are public. Anything else is visible to the posting organization and to
     * job moderators.
     */
    public function view(?User $user, JobPosting $job): bool
    {
        if ($job->status->equals(Published::class)) {
            return true;
        }

        return $user !== null
            && ($this->moderate($user) || OrganizationPermissions::allows($user, $job->organization, Permission::JobsView));
    }

    /**
     * Employers post their own jobs; agencies post only while they hold a usable mandate
     * (product rule 10, D-077).
     */
    public function create(User $user, Organization $organization): bool
    {
        return match ($organization->type) {
            OrganizationType::Employer => OrganizationPermissions::allows($user, $organization, Permission::JobsManage),
            OrganizationType::Agency => OrganizationPermissions::allows($user, $organization, Permission::JobsManage)
                && JobMandate::usableFor($organization)->isNotEmpty(),
            default => false,
        };
    }

    public function update(User $user, JobPosting $job): bool
    {
        return $job->isEditable()
            && OrganizationPermissions::allows($user, $job->organization, Permission::JobsManage);
    }

    public function submit(User $user, JobPosting $job): bool
    {
        return OrganizationPermissions::allows($user, $job->organization, Permission::JobsSubmit);
    }

    /**
     * Pause, resume and close.
     */
    public function manage(User $user, JobPosting $job): bool
    {
        return OrganizationPermissions::allows($user, $job->organization, Permission::JobsManage);
    }

    public function moderate(User $user, ?JobPosting $job = null): bool
    {
        return $user->hasStaffPermission(Permission::JobsModerate->value);
    }
}
