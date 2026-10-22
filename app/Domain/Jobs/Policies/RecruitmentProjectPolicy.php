<?php

namespace App\Domain\Jobs\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Employer recruitment projects (spec §6, D-095): anyone who sees the jobs sees the projects;
 * the hiring roles (`projects.manage`) create and change them.
 */
class RecruitmentProjectPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Employer
            && OrganizationPermissions::allows($user, $organization, Permission::JobsView);
    }

    public function view(User $user, RecruitmentProject $project): bool
    {
        return $this->viewAny($user, $project->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $organization->type === OrganizationType::Employer
            && OrganizationPermissions::allows($user, $organization, Permission::ProjectsManage);
    }

    public function update(User $user, RecruitmentProject $project): bool
    {
        return $this->create($user, $project->organization);
    }
}
