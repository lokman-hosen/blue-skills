<?php

namespace App\Domain\Mobility\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * The worker follows their case; the managing organization runs it; the employer of an agency job
 * follows it (D-074, product rule 8).
 */
class MobilityCasePolicy
{
    /**
     * An organization's list of cases.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::ApplicationsView)
            || OrganizationPermissions::allows($user, $organization, Permission::MobilityManage);
    }

    public function view(User $user, MobilityCase $case): bool
    {
        if ($user->isWorker()) {
            return $this->ownsCase($user, $case);
        }

        foreach ([$case->managingOrganization, $case->employer] as $organization) {
            if ($organization !== null && $this->viewAny($user, $organization)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Move stages, request and check documents, close the case.
     */
    public function manage(User $user, MobilityCase $case): bool
    {
        return $case->isOpen()
            && ! $user->isWorker()
            && $case->managingOrganization !== null
            && OrganizationPermissions::allows($user, $case->managingOrganization, Permission::MobilityManage);
    }

    /**
     * Upload, share, revoke and confirm the contract.
     */
    public function respond(User $user, MobilityCase $case): bool
    {
        return $case->isOpen() && $this->ownsCase($user, $case);
    }

    private function ownsCase(User $user, MobilityCase $case): bool
    {
        return $user->isWorker() && $case->application->workerProfile->user_id === $user->id;
    }
}
