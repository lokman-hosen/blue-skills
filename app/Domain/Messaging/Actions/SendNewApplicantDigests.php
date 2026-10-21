<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Notifications\AppNotification;
use App\Domain\Messaging\Support\Notify;
use Carbon\CarbonInterface;

/**
 * Once a day, one alert per job that received applications since the last run, to members who
 * can see applicants (D-068: organizations get new applicants as a daily digest).
 */
class SendNewApplicantDigests
{
    /**
     * @return int Notifications sent.
     */
    public function handle(CarbonInterface $since): int
    {
        $counts = Application::query()
            ->where('applied_at', '>=', $since)
            ->selectRaw('job_id, count(*) as total')
            ->groupBy('job_id')
            ->pluck('total', 'job_id');

        $sent = 0;

        JobPosting::query()->whereIn('id', $counts->keys())->with('organization')->get()
            ->each(function (JobPosting $job) use ($counts, &$sent): void {
                foreach (Notify::members($job->organization, Permission::ApplicationsView) as $member) {
                    $member->notify(new AppNotification(
                        NotificationEvent::NewApplicants,
                        ['job' => $job->title, 'count' => (int) $counts[$job->id]],
                        route('org.jobs.applicants', [$job, 'stage' => 'applied'], false),
                    ));
                    $sent++;
                }
            });

        return $sent;
    }
}
