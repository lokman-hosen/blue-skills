<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\States\RecruitmentProject\Active;
use App\Domain\Jobs\States\RecruitmentProject\Cancelled;
use App\Domain\Jobs\States\RecruitmentProject\Completed;
use Illuminate\Validation\ValidationException;

/**
 * Mark a project completed or cancelled, or reopen a completed one. Its jobs and applications
 * are not touched.
 */
class ChangeRecruitmentProjectStatus
{
    public const ACTIONS = ['complete', 'cancel', 'reopen'];

    public function handle(RecruitmentProject $project, string $action): void
    {
        $target = match ($action) {
            'complete' => Completed::class,
            'cancel' => Cancelled::class,
            default => Active::class,
        };

        if (! $project->status->canTransitionTo($target)) {
            throw ValidationException::withMessages(['status' => __('projects.errors.status')]);
        }

        $project->status->transitionTo($target);
    }
}
