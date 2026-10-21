<?php

namespace App\Domain\Institute\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * What an institute may do with a graduate (product rule 11): attest and assess only once the
 * graduate accepted and the institute's accreditation is verified; follow applications only
 * while the graduate shares progress (D-082). Never edit their profile or see their documents.
 */
class CohortMemberPolicy
{
    public function attest(User $user, CohortMember $member): bool
    {
        return $this->canIssue($user, $member, Permission::AttestationsIssue);
    }

    public function assess(User $user, CohortMember $member): bool
    {
        return $this->canIssue($user, $member, Permission::AssessmentsRecord);
    }

    public function viewProgress(User $user, CohortMember $member): bool
    {
        return $member->isAccepted() && $member->shares_progress
            && OrganizationPermissions::allows($user, $member->cohort->organization, Permission::PlacementsView);
    }

    /**
     * The graduate themselves: answer the invitation, change sharing, leave.
     */
    public function respond(User $user, CohortMember $member): bool
    {
        return $user->isWorker() && $member->workerProfile?->user_id === $user->id;
    }

    private function canIssue(User $user, CohortMember $member, Permission $permission): bool
    {
        $institute = $member->cohort->organization;

        return $member->isAccepted() && $institute->isVerified()
            && OrganizationPermissions::allows($user, $institute, $permission);
    }
}
