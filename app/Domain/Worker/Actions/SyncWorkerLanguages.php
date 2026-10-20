<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

class SyncWorkerLanguages
{
    /**
     * @param  list<array{code: string, proficiency: string}>  $languages
     */
    public function handle(WorkerProfile $profile, array $languages): void
    {
        DB::transaction(function () use ($profile, $languages): void {
            $profile->languages()->delete();

            foreach ($languages as $language) {
                $profile->languages()->create(['language_code' => $language['code'], 'proficiency' => $language['proficiency']]);
            }

            activity('worker')->performedOn($profile)->causedBy($profile->user)
                ->withProperties(['languages' => $languages])
                ->log('profile.languages_updated');
        });
    }
}
