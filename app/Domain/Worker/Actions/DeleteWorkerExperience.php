<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerExperience;

class DeleteWorkerExperience
{
    public function handle(WorkerExperience $experience): void
    {
        $profile = $experience->workerProfile;

        activity('worker')->performedOn($profile)->causedBy($profile->user)
            ->withProperties(['country' => $experience->country_code, 'role' => $experience->role])
            ->log('experience.deleted');

        $experience->delete();
    }
}
