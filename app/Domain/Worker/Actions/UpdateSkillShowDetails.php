<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use Illuminate\Support\Facades\DB;

/**
 * Skill tags and who can see the SkillShow. The video itself does not change, so it keeps
 * its moderation decision.
 */
class UpdateSkillShowDetails
{
    /**
     * @param  list<string>  $skillIds
     */
    public function handle(SkillShow $skillShow, array $skillIds, ProfileVisibility $visibility): void
    {
        DB::transaction(function () use ($skillShow, $skillIds, $visibility): void {
            $skillShow->skills()->sync($skillIds);
            $skillShow->update(['visibility' => $visibility]);

            activity('worker')->performedOn($skillShow)->causedBy($skillShow->workerProfile->user)
                ->withProperties(['skills' => $skillIds, 'visibility' => $visibility->value])
                ->log('skill_show.updated');
        });
    }
}
