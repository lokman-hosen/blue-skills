<?php

namespace App\Domain\Applications\Policies;

use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\Support\TalentAccess;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;

class JobInvitationPolicy
{
    /**
     * Members who may search talent invite workers they found there (D-065).
     */
    public function create(User $user, Organization $organization): bool
    {
        return TalentAccess::canSearch($user, $organization);
    }

    /**
     * The invited worker opens, applies or declines.
     */
    public function respond(User $user, JobInvitation $invitation): bool
    {
        return $user->isWorker() && $invitation->workerProfile->user_id === $user->id;
    }
}
