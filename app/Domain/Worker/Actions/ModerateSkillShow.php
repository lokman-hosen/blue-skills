<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\SkillShowRejectionReason;
use App\Domain\Worker\Models\SkillShow;
use Illuminate\Validation\ValidationException;

/**
 * A moderator publishes or rejects a SkillShow (product rule 3). The decision, moderator and
 * reason code go to the activity log with the before and after status.
 */
class ModerateSkillShow
{
    public function handle(SkillShow $skillShow, User $moderator, bool $approve, ?SkillShowRejectionReason $reason): SkillShow
    {
        if ($skillShow->moderation_status !== ModerationStatus::Pending) {
            throw ValidationException::withMessages(['decision' => __('admin.skillshows.not_pending')]);
        }

        $before = $skillShow->moderation_status;

        $skillShow->update([
            'moderation_status' => $approve ? ModerationStatus::Approved : ModerationStatus::Rejected,
            'reason_code' => $approve ? null : $reason?->value,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        activity('worker')->performedOn($skillShow)->causedBy($moderator)
            ->withProperties([
                'old' => ['moderation_status' => $before->value],
                'attributes' => ['moderation_status' => $skillShow->moderation_status->value],
                'reason_code' => $skillShow->reason_code,
            ])
            ->log($approve ? 'skill_show.approved' : 'skill_show.rejected');

        return $skillShow;
    }
}
