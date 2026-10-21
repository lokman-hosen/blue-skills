<?php

namespace App\Domain\Institute\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Course;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Every institute role can see the courses; Owners and Trainers (`courses.manage`) change them.
 */
class CoursePolicy
{
    /** Any of these lets a member work with the institute's courses and cohorts. */
    public const INSTITUTE_PERMISSIONS = [
        Permission::CoursesManage, Permission::CohortsManage, Permission::GraduatesInvite,
        Permission::AttestationsIssue, Permission::AssessmentsRecord, Permission::PlacementsView,
    ];

    public function viewAny(User $user, Organization $organization): bool
    {
        if ($organization->type !== OrganizationType::Institute) {
            return false;
        }

        foreach (self::INSTITUTE_PERMISSIONS as $permission) {
            if (OrganizationPermissions::allows($user, $organization, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Institute
            && OrganizationPermissions::allows($user, $organization, Permission::CoursesManage);
    }

    public function view(User $user, Course $course): bool
    {
        return $this->viewAny($user, $course->organization);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->create($user, $course->organization);
    }
}
