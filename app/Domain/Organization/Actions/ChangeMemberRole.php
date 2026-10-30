<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\RoleDirectory;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Organization\Support\OrganizationRoles;
use Illuminate\Validation\ValidationException;

class ChangeMemberRole
{
    public function __construct(
        private readonly OrganizationRoles $roles,
        private readonly RoleDirectory $directory,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(OrganizationMember $member, OrganizationRole|string $role, User $actor): void
    {
        $role = $role instanceof OrganizationRole ? $role->value : $role;
        $organization = $member->organization;

        if (! $this->directory->fitsOrganization($role, $organization->type)) {
            throw ValidationException::withMessages(['role' => __('team.role_not_allowed')]);
        }

        $previous = $this->roles->nameOf($member->user, $organization);

        if (! $this->directory->isOwner($role) && $this->roles->isLastOwner($member->user, $organization)) {
            throw ValidationException::withMessages(['role' => __('team.last_owner')]);
        }

        $this->roles->assign($member->user, $organization, $role);

        activity('organization')->performedOn($organization)->causedBy($actor)
            ->withProperties(['member' => $member->user_id, 'old' => $previous, 'new' => $role])
            ->log('member.role_changed');
    }
}
