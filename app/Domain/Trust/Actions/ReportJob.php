<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\States\Report\Open;

/**
 * "Report this job" from the Truth Card (spec §5, §10). Trust officers work the queue in Module 8.
 */
class ReportJob
{
    public function handle(JobPosting $job, User $reporter, ReportCategory $category, ?string $description): Report
    {
        $report = Report::query()->create([
            'reporter_user_id' => $reporter->id,
            'subject_type' => $job->getMorphClass(),
            'subject_id' => $job->id,
            'category' => $category,
            'description' => $description,
            'status' => Open::class,
        ]);

        activity('trust')->performedOn($job)->causedBy($reporter)
            ->withProperties(['report' => $report->id, 'category' => $category->value])
            ->log('job.reported');

        return $report;
    }
}
