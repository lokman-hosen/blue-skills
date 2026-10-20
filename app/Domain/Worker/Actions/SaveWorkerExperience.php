<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;

/**
 * Adds or edits a period of work. The worker gives either dates or a number of years.
 */
class SaveWorkerExperience
{
    public function __construct(private readonly ProfileCompletion $completion) {}

    /**
     * @param  array{country_code: string, role: string, employer_name: string|null, years: int|null, started_on: string|null, ended_on: string|null, description: string|null}  $fields
     */
    public function handle(WorkerProfile $profile, ?WorkerExperience $experience, array $fields): WorkerExperience
    {
        // Dates win over a stated number of years, so the two can never disagree.
        if ($fields['started_on'] !== null) {
            $fields['years'] = null;
        }

        $experience ??= new WorkerExperience(['worker_profile_id' => $profile->id]);
        $experience->fill($fields)->save();

        activity('worker')->performedOn($experience)->causedBy($profile->user)
            ->withProperties(['attributes' => $fields])
            ->log($experience->wasRecentlyCreated ? 'experience.added' : 'experience.updated');

        $this->completion->refresh($profile);

        return $experience;
    }
}
