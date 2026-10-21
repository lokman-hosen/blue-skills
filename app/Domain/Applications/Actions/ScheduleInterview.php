<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\InterviewMode;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\States\Application\Interview as InterviewStage;
use App\Domain\Applications\Support\StageChange;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books an interview and, if the applicant is not at the interview stage yet, moves them there.
 * The worker sees the time, mode, place and instructions on their tracker (D-060: in-app only).
 */
class ScheduleInterview
{
    /**
     * @param  array{scheduled_at: CarbonInterface, duration_minutes: int, mode: InterviewMode, location: ?string, instructions: ?string}  $details
     */
    public function handle(Application $application, array $details, User $actor): Interview
    {
        if ($application->isClosed()) {
            throw ValidationException::withMessages(['scheduled_at' => __('applications.interview.closed')]);
        }

        return DB::transaction(function () use ($application, $details, $actor): Interview {
            /** @var Interview $interview */
            $interview = $application->interviews()->create([...$details, 'scheduled_by' => $actor->id]);

            if (! $application->status->equals(InterviewStage::class) && $application->status->canTransitionTo(InterviewStage::class)) {
                StageChange::apply($application, InterviewStage::class, $actor);
            }

            activity('applications')->performedOn($application)->causedBy($actor)
                ->withProperties(['interview' => $interview->id, 'scheduled_at' => $interview->scheduled_at->toIso8601String(), 'mode' => $interview->mode->value])
                ->log('interview.scheduled');

            Notify::worker($application, NotificationEvent::InterviewScheduled, ['at' => $interview->scheduled_at->toIso8601String()]);

            return $interview;
        });
    }
}
