<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\RecruitmentProject\Active;
use App\Domain\Jobs\Support\HiringAnalytics;
use App\Domain\Jobs\Support\HiringStages;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Support\Data\StatusData;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/**
 * An organization's hiring analytics for a date range (spec §14): stage counts, time to
 * shortlist and offer, where applicants come from, each job and each active project.
 */
class HiringAnalyticsData extends Data
{
    /**
     * @param  list<array{key: string, count: int}>  $stages
     * @param  list<array{id: string, code: string, title: string, status: StatusData, counts: array<string, int>}>  $jobs
     * @param  list<array{code: string, count: int}>  $countries
     * @param  array{invited: int, direct: int, withdrawn: int}  $sources
     * @param  list<RecruitmentProjectListItemData>  $projects
     */
    public function __construct(
        public int $liveJobs,
        public array $stages,
        public ?float $medianDaysToShortlist,
        public ?float $medianDaysToOffer,
        public array $jobs,
        public array $countries,
        public array $sources,
        public array $projects,
    ) {}

    public static function for(Organization $organization, CarbonInterface $from, CarbonInterface $to): self
    {
        $jobs = JobPosting::query()->where('organization_id', $organization->id)->orderBy('title')->get();
        /** @var list<string> $jobIds */
        $jobIds = $jobs->modelKeys();
        $byJob = HiringStages::byJob($jobIds, $from, $to);
        $totals = array_fill_keys(HiringStages::STAGES, 0);

        foreach ($byJob as $counts) {
            foreach ($counts as $stage => $count) {
                $totals[$stage] += $count;
            }
        }

        $median = HiringAnalytics::medianDays($jobIds, $from, $to);

        return new self(
            liveJobs: $jobs->filter(fn (JobPosting $job) => $job->status->equals(Published::class))->count(),
            stages: array_map(fn (string $key) => ['key' => $key, 'count' => $totals[$key]], HiringStages::STAGES),
            medianDaysToShortlist: $median['shortlist'],
            medianDaysToOffer: $median['offer'],
            jobs: $jobs
                ->filter(fn (JobPosting $job) => $byJob[$job->id]['applied'] > 0 || $job->status->equals(Published::class))
                ->sortByDesc(fn (JobPosting $job) => $byJob[$job->id]['applied'])
                ->take(50)
                ->map(fn (JobPosting $job) => [
                    'id' => $job->id,
                    'code' => $job->public_code,
                    'title' => $job->title,
                    'status' => StatusData::fromState($job->status),
                    'counts' => $byJob[$job->id],
                ])->values()->all(),
            countries: HiringAnalytics::countries($jobIds, $from, $to),
            sources: HiringAnalytics::sources($jobIds, $from, $to),
            projects: $organization->type === OrganizationType::Employer
                ? $organization->recruitmentProjects()->whereState('status', Active::class)->latest()->get()
                    ->map(fn (RecruitmentProject $project) => RecruitmentProjectListItemData::fromProject($project))->values()->all()
                : [],
        );
    }
}
