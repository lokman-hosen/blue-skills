<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Organization\Support\OrganizationRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveOrganizationMember
{
    public function __construct(private readonly OrganizationRoles $roles) {}

    /**
     * @throws ValidationException
     */
    public function handle(OrganizationMember $member, User $actor): void
    {
        $organization = $member->organization;

        if ($this->roles->isLastOwner($member->user, $organization)) {
            throw ValidationException::withMessages(['member' => __('team.last_owner')]);
        }

        DB::transaction(function () use ($member, $organization, $actor): void {
            $this->roles->revokeAll($member->user, $organization);
            $member->delete();

            activity('organization')->performedOn($organization)->causedBy($actor)
                ->withProperties(['member' => $member->user_id])
                ->log('member.removed');
        });
    }
}
