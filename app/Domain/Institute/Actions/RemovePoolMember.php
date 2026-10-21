<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Worker\Models\WorkerProfile;

class RemovePoolMember
{
    public function handle(TalentPool $pool, WorkerProfile $profile, User $actor): void
    {
        $pool->workers()->detach($profile->id);

        activity('institute')->performedOn($pool)->causedBy($actor)
            ->withProperties(['removed' => $profile->id])
            ->log('talent_pool.member_removed');
    }
}
