<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\Application;

/**
 * The worker has seen that the job changed after they applied (product rule 5).
 */
class AcknowledgeJobChange
{
    public function handle(Application $application): void
    {
        $application->update(['job_change_seen_at' => now()]);

        activity('applications')->performedOn($application)->causedBy($application->workerProfile->user)
            ->withProperties(['job_changed_at' => $application->job->material_change_at?->toIso8601String()])
            ->log('application.job_change_seen');
    }
}
