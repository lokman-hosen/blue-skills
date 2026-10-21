<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Applications\Support\ApplicationStages;
use App\Domain\Applications\Support\StageChange;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The organization moves one or many applicants to a pipeline stage (spec §6, deck slide 18).
 * Offers, acceptance and withdrawal have their own actions; rejection needs a reason.
 * Applicants that cannot make the move (closed, or already past it) are skipped, not failed.
 */
class MoveApplications
{
    /**
     * @param  Collection<int, Application>  $applications
     * @return array{moved: int, skipped: int}
     */
    public function handle(Collection $applications, string $stage, User $actor): array
    {
        if (! in_array($stage, ApplicationStages::MOVABLE, true)) {
            throw ValidationException::withMessages(['status' => __('applications.stage.not_allowed')]);
        }

        /** @var class-string<ApplicationState> $target */
        $target = ApplicationState::resolveStateClass($stage);
        $moved = 0;

        foreach ($applications as $application) {
            if (! $application->status->canTransitionTo($target)) {
                continue;
            }

            DB::transaction(fn () => StageChange::apply($application, $target, $actor));
            $moved++;
        }

        if ($moved === 0) {
            throw ValidationException::withMessages(['status' => __('applications.stage.none_moved')]);
        }

        return ['moved' => $moved, 'skipped' => $applications->count() - $moved];
    }
}
