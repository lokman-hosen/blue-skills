<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

class OrganizationPolicy
{
    /**
     * Members may switch their workspace to an organization they belong to.
     */
    public function switchTo(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::isMember($user, $organization);
    }

    /**
     * Staff review organization registrations (spec §10).
     */
    public function review(User $user, ?Organization $organization = null): bool
    {
        return $user->hasStaffPermission(Permission::VerificationsReview->value);
    }

    /**
     * Organization users (not workers or staff) may register an organization.
     */
    public function create(User $user): bool
    {
        return $user->type === UserType::Org;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->hasPermission($user, $organization, Permission::OrganizationUpdate);
    }

    public function viewTeam(User $user, Organization $organization): bool
    {
        return $this->switchTo($user, $organization);
    }

    public function manageTeam(User $user, Organization $organization): bool
    {
        return $this->hasPermission($user, $organization, Permission::TeamManage);
    }

    /**
     * An agency's compliance overview: licence, mandates and the jobs they back (D-080).
     */
    public function reviewCompliance(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Agency
            && $this->hasPermission($user, $organization, Permission::ComplianceReview);
    }

    /**
     * An institute's placements page: graduates who share their progress (D-082).
     */
    public function viewPlacements(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Institute
            && $this->hasPermission($user, $organization, Permission::PlacementsView);
    }

    /**
     * The workspace billing page: plan, credits, seats and invoices (D-095).
     */
    public function viewBilling(User $user, Organization $organization): bool
    {
        return $organization->type !== OrganizationType::Institute
            && $this->hasPermission($user, $organization, Permission::BillingManage);
    }

    /**
     * Hiring analytics: counts only, for anyone who sees the applicants (D-095).
     */
    public function viewAnalytics(User $user, Organization $organization): bool
    {
        return $organization->type !== OrganizationType::Institute
            && $this->hasPermission($user, $organization, Permission::ApplicationsView);
    }

    private function hasPermission(User $user, Organization $organization, Permission $permission): bool
    {
        return OrganizationPermissions::allows($user, $organization, $permission);
    }
}
