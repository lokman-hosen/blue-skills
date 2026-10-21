<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Archived;
use App\Domain\Jobs\States\Job\Closed;
use App\Domain\Jobs\States\Job\JobState;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\Support\JobCompliance;
use Illuminate\Validation\ValidationException;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

/**
 * Employer pauses, resumes or closes a job, or archives a draft. Allowed moves come from the
 * job state machine; anything else is refused with a clear message.
 */
class ChangeJobAvailability
{
    public const ACTIONS = ['pause', 'resume', 'close', 'archive'];

    public function handle(JobPosting $job, string $action, User $actor): JobPosting
    {
        /** @var class-string<JobState> $target */
        $target = match ($action) {
            'pause' => Paused::class,
            'resume' => Published::class,
            'close' => Closed::class,
            'archive' => Archived::class,
            default => throw ValidationException::withMessages(['job' => __('jobs.availability.not_allowed')]),
        };

        // An agency job goes live again only under a usable mandate (product rule 10, D-080).
        if ($action === 'resume' && ($problems = JobCompliance::mandateProblems($job)) !== []) {
            throw ValidationException::withMessages(['job' => reset($problems)]);
        }

        try {
            $job->status->transitionTo($target);
        } catch (CouldNotPerformTransition) {
            throw ValidationException::withMessages(['job' => __('jobs.availability.not_allowed')]);
        }

        activity('jobs')->performedOn($job)->causedBy($actor)->log("job.{$action}");

        return $job;
    }
}
