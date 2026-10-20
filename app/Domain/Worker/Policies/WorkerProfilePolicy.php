<?php

namespace App\Domain\Worker\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Worker\Models\WorkerProfile;

/**
 * The worker owns the profile (product rule 1). Organizations only ever receive projections
 * through applications and search, never this model.
 */
class WorkerProfilePolicy
{
    public function view(User $user, WorkerProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    public function update(User $user, WorkerProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    private function owns(User $user, WorkerProfile $profile): bool
    {
        return $user->isWorker() && $profile->user_id === $user->id;
    }
}
