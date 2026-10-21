<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\Report\Investigating;
use App\Domain\Trust\States\Report\Open as OpenReport;
use App\Domain\Trust\States\TrustCase\Open;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * Opens a trust case about an organization, job or worker, taking in the reports that led to it
 * (spec §10). Staff can also open one without a report.
 */
class OpenTrustCase
{
    /**
     * @param  iterable<Report>  $reports
     */
    public function handle(Organization|JobPosting|WorkerProfile $subject, ReportCategory $category, CasePriority $priority, string $summary, iterable $reports, User $actor): TrustCase
    {
        return DB::transaction(function () use ($subject, $category, $priority, $summary, $reports, $actor): TrustCase {
            $case = TrustCase::query()->create([
                'reference' => TrustCase::newReference(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'category' => $category,
                'priority' => $priority,
                'status' => Open::class,
                'summary' => $summary,
                'opened_by' => $actor->id,
                'assigned_to' => $actor->id,
            ]);

            foreach ($reports as $report) {
                self::attach($report, $case);
            }

            activity('trust')->performedOn($case)->causedBy($actor)
                ->withProperties(['subject' => [$case->subject_type, $case->subject_id], 'category' => $category->value, 'priority' => $priority->value])
                ->log('trust_case.opened');

            return $case;
        });
    }

    public static function attach(Report $report, TrustCase $case): void
    {
        $report->update(['trust_case_id' => $case->id]);

        if ($report->status->equals(OpenReport::class)) {
            $report->status->transitionTo(Investigating::class);
        }
    }
}
