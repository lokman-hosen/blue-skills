<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\JobSkillRequirement;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Jobs\Support\JobWizard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Copies a job into a new draft of the same organization, so a multi-position campaign does not
 * have to retype shared details (job posting prototype, step 1; D-117). The copy has its own
 * code, starts unreviewed and has no applications. A passed deadline or joining window is dropped.
 * An agency copy must still fit its mandate (product rule 10).
 */
class DuplicateJob
{
    /**
     * Never copied: identity, lifecycle and review history (replicate() already skips the id and
     * timestamps).
     */
    private const SKIPPED = ['public_code', 'published_at', 'submitted_at', 'material_change_at', 'deleted_at'];

    public function handle(JobPosting $job, User $actor): JobPosting
    {
        $job->loadMissing(['skillRequirements', 'costs']);

        $copy = $job->replicate(self::SKIPPED);
        $copy->forceFill([
            'public_code' => JobWizard::code($job->destination_country_code, $job->occupation_id),
            'status' => Draft::class,
            'deadline' => $job->deadline?->isPast() && ! $job->deadline->isToday() ? null : $job->deadline,
            'joining_from' => $job->joining_from?->isPast() ? null : $job->joining_from,
            'joining_to' => $job->joining_to?->isPast() ? null : $job->joining_to,
        ]);

        $problems = JobCompliance::mandateProblems($copy);

        if ($problems !== []) {
            throw ValidationException::withMessages(['job' => array_values($problems)[0]]);
        }

        return DB::transaction(function () use ($job, $copy, $actor): JobPosting {
            $copy->save();

            $copy->skillRequirements()->createMany($job->skillRequirements->map(
                fn (JobSkillRequirement $requirement) => $requirement->only(['skill_id', 'required']),
            )->all());

            $copy->costs()->createMany($job->costs->map(fn (JobCost $cost) => $cost->only([
                'category', 'description', 'amount_minor', 'currency', 'payer',
            ]))->all());

            activity('jobs')->performedOn($copy)->causedBy($actor)
                ->withProperties(['copied_from' => $job->public_code])
                ->log('job.duplicated');

            return $copy;
        });
    }
}
