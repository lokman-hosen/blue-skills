<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use Illuminate\Support\Facades\DB;

/**
 * Daily (D-080): verified mandates past their end date become expired, and live agency jobs whose
 * mandate can no longer back them (expired, revoked, licence lapsed or no longer covering the
 * destination) are paused so workers stop seeing them (product rule 10). The agency is told and
 * can resume once a usable mandate is in place.
 */
class PauseJobsUnderLapsedMandates
{
    /**
     * @return int The number of jobs paused.
     */
    public function handle(): int
    {
        JobMandate::query()
            ->where('status', MandateStatus::Verified)
            ->whereDate('valid_until', '<', now()->toDateString())
            ->get()
            ->each(function (JobMandate $mandate): void {
                $mandate->update(['status' => MandateStatus::Expired]);
                activity('mandates')->performedOn($mandate)
                    ->withProperties(['old' => ['status' => MandateStatus::Verified->value], 'attributes' => ['status' => MandateStatus::Expired->value], 'reason' => 'valid_until_passed'])
                    ->log('mandate.expired');
            });

        $paused = 0;

        JobPosting::query()
            ->whereState('status', Published::class)
            ->whereNotNull('agency_organization_id')
            ->with(['mandate.agency', 'organization'])
            ->get()
            ->filter(fn (JobPosting $job) => array_key_exists('job_mandate_id', JobCompliance::mandateProblems($job)))
            ->each(function (JobPosting $job) use (&$paused): void {
                DB::transaction(function () use ($job): void {
                    $job->status->transitionTo(Paused::class);
                    activity('jobs')->performedOn($job)
                        ->withProperties(['reason' => 'mandate_lapsed', 'mandate' => $job->mandate?->reference])
                        ->log('job.paused_mandate_lapsed');
                });

                foreach (Notify::members($job->organization, Permission::JobsManage) as $member) {
                    Notify::user($member, NotificationEvent::JobPausedMandate, [
                        'job' => $job->title,
                        'code' => $job->public_code,
                        'reference' => (string) $job->mandate?->reference,
                    ], route('org.jobs.show', $job, false));
                }

                $paused++;
            });

        return $paused;
    }
}
