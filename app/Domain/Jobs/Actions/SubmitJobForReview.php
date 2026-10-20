<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\MoreInformationRequested;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Verification\Enums\VerificationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sends a job to staff review. Only verified employers may submit (D-036); the destination's
 * mandatory disclosures must be filled in and every worker-paid cost allowed (D-053).
 */
class SubmitJobForReview
{
    /**
     * @param  list<string>  $materialChanges  Set when a live job changed materially.
     */
    public function handle(JobPosting $job, User $actor, array $materialChanges = []): JobPosting
    {
        $job->loadMissing('organization');

        if (! $job->organization->isVerified()) {
            throw ValidationException::withMessages(['job' => __('jobs.submit.organization_not_verified')]);
        }

        if (! $job->status->equals(Draft::class, MoreInformationRequested::class, Published::class, Paused::class)) {
            throw ValidationException::withMessages(['job' => __('jobs.submit.not_submittable')]);
        }

        $problems = JobCompliance::problems($job);

        if ($problems !== []) {
            throw ValidationException::withMessages($problems);
        }

        return DB::transaction(function () use ($job, $actor, $materialChanges): JobPosting {
            $job->status->transitionTo(Submitted::class);
            $job->forceFill(['submitted_at' => now()])->save();

            $job->verificationRequests()->create([
                'type' => VerificationType::Job,
                // Tells the reviewer why a live job is back (product rule 5).
                'notes' => $materialChanges !== [] ? 'Material change: '.implode(', ', $materialChanges) : null,
            ]);

            activity('jobs')->performedOn($job)->causedBy($actor)
                ->withProperties(['material_changes' => $materialChanges])
                ->log($materialChanges !== [] ? 'job.resubmitted_after_change' : 'job.submitted');

            return $job;
        });
    }
}
