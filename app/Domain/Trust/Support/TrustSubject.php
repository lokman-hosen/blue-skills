<?php

namespace App\Domain\Trust\Support;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Suspended as JobSuspended;
use App\Domain\Messaging\Enums\MessageSide;
use App\Domain\Messaging\Models\Message;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Suspended as OrganizationSuspended;
use App\Domain\Trust\Models\Report;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Model;

/**
 * What a trust case or suspension can be about: an organization, a job or a worker (D-089).
 */
final class TrustSubject
{
    public const TYPES = ['organization', 'job', 'worker_profile'];

    /**
     * Who a report is really about: the job itself, or whoever sent a reported message.
     */
    public static function fromReport(Report $report): Organization|JobPosting|WorkerProfile|null
    {
        $subject = $report->subject;

        return match (true) {
            $subject instanceof JobPosting => $subject,
            $subject instanceof Message => $subject->side === MessageSide::Worker
                ? $subject->conversation->workerProfile
                : $subject->conversation->organization,
            default => null,
        };
    }

    public static function label(?Model $subject): string
    {
        return match (true) {
            $subject instanceof Organization => (string) $subject->display_name,
            $subject instanceof JobPosting => "{$subject->public_code} · {$subject->title}",
            $subject instanceof WorkerProfile => trim("{$subject->user?->name} · {$subject->reference}", ' ·'),
            default => '—',
        };
    }

    public static function isSuspended(Model $subject): bool
    {
        return match (true) {
            $subject instanceof Organization => $subject->verification_status->equals(OrganizationSuspended::class),
            $subject instanceof JobPosting => $subject->status->equals(JobSuspended::class),
            $subject instanceof WorkerProfile => $subject->isSuspended(),
            default => false,
        };
    }
}
