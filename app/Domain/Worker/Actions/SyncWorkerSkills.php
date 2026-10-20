<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;
use Illuminate\Support\Facades\DB;

/**
 * The worker's own skill list. Skills an institute attested cannot be removed here
 * (product rule 11 keeps attestations with the institute).
 */
class SyncWorkerSkills
{
    public function __construct(private readonly ProfileCompletion $completion) {}

    /**
     * @param  list<string>  $skillIds
     */
    public function handle(WorkerProfile $profile, array $skillIds): void
    {
        DB::transaction(function () use ($profile, $skillIds): void {
            $removed = $profile->workerSkills()
                ->where('source', '!=', SkillSource::Institute)
                ->whereNotIn('skill_id', $skillIds)
                ->pluck('skill_id')
                ->all();

            $profile->workerSkills()->whereIn('skill_id', $removed)->delete();

            $added = array_values(array_diff($skillIds, $profile->workerSkills()->pluck('skill_id')->all()));

            foreach ($added as $skillId) {
                $profile->workerSkills()->create(['skill_id' => $skillId, 'source' => SkillSource::Typed, 'confirmed_at' => now()]);
            }

            if ($added !== [] || $removed !== []) {
                activity('worker')->performedOn($profile)->causedBy($profile->user)
                    ->withProperties(['added' => $added, 'removed' => $removed])
                    ->log('profile.skills_updated');
            }
        });

        $this->completion->refresh($profile);
    }
}
