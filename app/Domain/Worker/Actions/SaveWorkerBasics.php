<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;

/**
 * Onboarding phase A: trade and years of experience, entered directly by the worker.
 */
class SaveWorkerBasics
{
    public function __construct(private readonly ProfileCompletion $completion) {}

    public function handle(WorkerProfile $profile, string $occupationId, ?string $specializationId, int $yearsExperience): WorkerProfile
    {
        $profile->fill([
            'occupation_id' => $occupationId,
            'specialization_id' => $specializationId,
            'years_experience' => $yearsExperience,
        ]);

        if ($profile->isDirty()) {
            $changes = $profile->getDirty();
            $profile->save();

            activity('worker')->performedOn($profile)->causedBy($profile->user)
                ->withProperties(['attributes' => $changes])
                ->log('profile.basics_saved');
        }

        $this->completion->refresh($profile);

        return $profile;
    }
}
