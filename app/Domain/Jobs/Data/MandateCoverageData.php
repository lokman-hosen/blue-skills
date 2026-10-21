<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use Spatie\LaravelData\Data;

/**
 * A mandate on the agency compliance page (D-080): whether it still backs jobs, places used and
 * left, and whether it ends soon.
 */
class MandateCoverageData extends Data
{
    public const EXPIRING_DAYS = 30;

    public function __construct(
        public MandateData $mandate,
        public int $placesLeft,
        public int $liveJobs,
        public bool $expiringSoon,
    ) {}

    public static function fromMandate(JobMandate $mandate): self
    {
        $until = $mandate->valid_until;

        return new self(
            mandate: MandateData::fromMandate($mandate),
            placesLeft: $mandate->headcountLeft(),
            liveJobs: $mandate->jobs->filter(fn (JobPosting $job) => $job->status->equals(Published::class))->count(),
            expiringSoon: $mandate->isUsable() && $until !== null && $until->lte(now()->addDays(self::EXPIRING_DAYS)),
        );
    }
}
