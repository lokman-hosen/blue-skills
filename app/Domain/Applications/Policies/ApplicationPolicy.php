<?php

namespace App\Domain\Applications\Policies;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * The worker sees and controls their own applications. The organization that posted the job
 * sees the snapshot and consented items through its roles (product rule 1).
 */
class ApplicationPolicy
{
    /**
     * The organization's applicant lists.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::ApplicationsView);
    }

    public function view(User $user, Application $application): bool
    {
        return $this->owns($user, $application) || $this->organizationAllows($user, $application, Permission::ApplicationsView);
    }

    /**
     * Workers apply; whether this job takes applications is checked by CreateApplication.
     */
    public function create(User $user, JobPosting $job): bool
    {
        // Suspended workers can't apply (D-088).
        return $user->isWorker() && $user->workerProfile !== null && ! $user->workerProfile->isSuspended();
    }

    /**
     * Withdraw, answer an offer, revoke shared items, acknowledge a job change.
     */
    public function respond(User $user, Application $application): bool
    {
        return $this->owns($user, $application);
    }

    /**
     * Move stages, reject, assign a recruiter and send offers.
     */
    public function manage(User $user, Application $application): bool
    {
        return $this->organizationAllows($user, $application, Permission::ApplicationsManage);
    }

    public function scheduleInterview(User $user, Application $application): bool
    {
        return $this->organizationAllows($user, $application, Permission::InterviewsManage);
    }

    private function owns(User $user, Application $application): bool
    {
        return $user->isWorker() && $application->workerProfile()->where('user_id', $user->id)->exists();
    }

    private function organizationAllows(User $user, Application $application, Permission $permission): bool
    {
        return ! $user->isWorker()
            && OrganizationPermissions::allows($user, $application->job->organization, $permission);
    }
}
