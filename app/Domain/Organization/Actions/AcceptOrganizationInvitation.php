<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Support\OrganizationRoles;
use Illuminate\Support\Facades\DB;

class AcceptOrganizationInvitation
{
    public function __construct(private readonly OrganizationRoles $roles) {}

    public function handle(OrganizationInvitation $invitation, User $user): Organization
    {
        return DB::transaction(function () use ($invitation, $user): Organization {
            $organization = $invitation->organization;

            if (! $organization->users()->whereKey($user->id)->exists()) {
                $organization->users()->attach($user->id, ['invited_by' => $invitation->invited_by, 'joined_at' => now()]);
            }

            $this->roles->assign($user, $organization, $invitation->role);
            $invitation->forceFill(['accepted_at' => now()])->save();

            activity('organization')->performedOn($organization)->causedBy($user)
                ->withProperties(['role' => $invitation->role->value])
                ->log('member.joined');

            return $organization;
        });
    }
}
