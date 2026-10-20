<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;

class UpdatePassportAbout
{
    public function __construct(private readonly ProfileCompletion $completion) {}

    /**
     * @param  array{headline: string|null, occupation_id: string, specialization_id: string|null, years_experience: int, current_country_code: string}  $fields
     */
    public function handle(WorkerProfile $profile, array $fields): WorkerProfile
    {
        $profile->fill($fields);
        $changes = $profile->getDirty();
        $profile->save();

        if ($changes !== []) {
            activity('worker')->performedOn($profile)->causedBy($profile->user)
                ->withProperties(['attributes' => $changes])
                ->log('profile.about_updated');
        }

        $this->completion->refresh($profile);

        return $profile;
    }
}
