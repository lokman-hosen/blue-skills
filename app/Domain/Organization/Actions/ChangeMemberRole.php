<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Organization\Support\OrganizationRoles;
use Illuminate\Validation\ValidationException;

class ChangeMemberRole
{
    public function __construct(private readonly OrganizationRoles $roles) {}

    /**
     * @throws ValidationException
     */
    public function handle(OrganizationMember $member, OrganizationRole $role, User $actor): void
    {
        $organization = $member->organization;

        if ($role->organizationType() !== $organization->type) {
            throw ValidationException::withMessages(['role' => __('team.role_not_allowed')]);
        }

        $previous = $this->roles->roleOf($member->user, $organization);

        if (! $role->isOwner() && $this->roles->isLastOwner($member->user, $organization)) {
            throw ValidationException::withMessages(['role' => __('team.last_owner')]);
        }

        $this->roles->assign($member->user, $organization, $role);

        activity('organization')->performedOn($organization)->causedBy($actor)
            ->withProperties(['member' => $member->user_id, 'old' => $previous?->value, 'new' => $role->value])
            ->log('member.role_changed');
    }
}
