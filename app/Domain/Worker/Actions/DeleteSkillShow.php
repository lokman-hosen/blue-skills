<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\SkillShow;

class DeleteSkillShow
{
    public function handle(SkillShow $skillShow): void
    {
        $profile = $skillShow->workerProfile;

        activity('worker')->performedOn($profile)->causedBy($profile->user)
            ->withProperties(['skill_show' => $skillShow->id, 'status' => $skillShow->moderation_status->value])
            ->log('skill_show.deleted');

        // Deleting the model removes its media files as well.
        $skillShow->delete();
    }
}
