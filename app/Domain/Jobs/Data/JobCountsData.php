<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Organization\Models\Organization;
use Spatie\LaravelData\Data;

/**
 * The organization dashboard's job counts by where each job stands.
 */
class JobCountsData extends Data
{
    public function __construct(
        public int $live,
        public int $inReview,
        public int $needsChanges,
        public int $drafts,
    ) {}

    public static function forOrganization(Organization $organization): self
    {
        /** @var array<string, int> $counts */
        $counts = $organization->postedJobs()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        return new self(
            live: ($counts['published'] ?? 0) + ($counts['paused'] ?? 0),
            inReview: ($counts['submitted'] ?? 0) + ($counts['under_review'] ?? 0),
            needsChanges: ($counts['more_information_requested'] ?? 0) + ($counts['rejected'] ?? 0),
            drafts: $counts['draft'] ?? 0,
        );
    }
}
