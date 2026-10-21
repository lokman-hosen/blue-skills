<?php

namespace App\Domain\Applications\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;

/**
 * Who may find and view a worker outside an application (spec §8, D-060): verified
 * organizations whose member has `talent.search`, and only workers who chose to be found.
 * Applicants who did not choose that are seen only through their application, limited to the
 * items they shared (product rule 6).
 */
final class TalentAccess
{
    public static function canSearch(User $user, Organization $organization): bool
    {
        return $organization->isVerified() && OrganizationPermissions::allows($user, $organization, Permission::TalentSearch);
    }

    public static function canView(Organization $organization, WorkerProfile $profile): bool
    {
        return $organization->isVerified() && $profile->isDiscoverable();
    }

    /**
     * An approved SkillShow the worker made visible to employers and agencies, of a worker who
     * can be found, seen by a member of any verified organization that may search.
     */
    public static function canWatch(User $user, SkillShow $skillShow): bool
    {
        if ($user->isWorker() || ! $skillShow->isPublished() || $skillShow->visibility !== ProfileVisibility::EmployersAndAgencies) {
            return false;
        }

        if (! $skillShow->workerProfile->isDiscoverable()) {
            return false;
        }

        return $user->organizations()->get()->contains(fn (Organization $organization) => self::canSearch($user, $organization));
    }
}
