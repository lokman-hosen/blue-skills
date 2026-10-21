<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use App\Domain\Organization\Models\Organization;
use Spatie\LaravelData\Data;

/**
 * Applicant numbers across the organization's jobs for the dashboard.
 */
class ApplicantCountsData extends Data
{
    public function __construct(
        public int $total,
        public int $new,
        public int $shortlisted,
        public int $interviewsUpcoming,
    ) {}

    public static function forOrganization(Organization $organization): self
    {
        $applications = Application::query()->whereHas('job', fn ($query) => $query->where('organization_id', $organization->id));

        /** @var array<string, int> $counts */
        $counts = (clone $applications)->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        return new self(
            total: array_sum($counts),
            new: $counts['applied'] ?? 0,
            shortlisted: $counts['shortlisted'] ?? 0,
            interviewsUpcoming: Interview::query()
                ->whereIn('application_id', (clone $applications)->select('id'))
                ->whereNull('outcome')
                ->where('scheduled_at', '>', now())
                ->count(),
        );
    }
}
