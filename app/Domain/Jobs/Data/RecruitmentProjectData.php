<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\Support\ProjectStats;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Support\Data\StatusData;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\Data;

/**
 * A recruitment project with its stage counts, jobs and team (spec §6, deck slide 18).
 */
class RecruitmentProjectData extends Data
{
    /**
     * @param  list<array{key: string, count: int}>  $stages
     * @param  list<array{id: string, code: string, title: string, status: StatusData, headcount: int, applicants: int, joined: int}>  $jobs
     * @param  list<array{id: string, name: string}>  $team
     */
    public function __construct(
        public RecruitmentProjectListItemData $summary,
        public array $stages,
        public array $jobs,
        public array $team,
    ) {}

    public static function fromProject(RecruitmentProject $project): self
    {
        $jobs = $project->jobs()->withCount([
            'applications',
            'applications as joined_count' => fn (Builder $query) => $query
                ->whereHas('mobilityCase', fn (Builder $case) => $case->where('status', Completed::$name)),
        ])->orderBy('title')->get();
        /** @var list<string> $jobIds */
        $jobIds = $jobs->modelKeys();
        $counts = ProjectStats::for($jobIds);

        return new self(
            summary: RecruitmentProjectListItemData::fromProject($project),
            stages: array_map(fn (string $key) => ['key' => $key, 'count' => $counts[$key]], ProjectStats::STAGES),
            jobs: $jobs->map(fn (JobPosting $job) => [
                'id' => $job->id,
                'code' => $job->public_code,
                'title' => $job->title,
                'status' => StatusData::fromState($job->status),
                'headcount' => (int) $job->headcount,
                'applicants' => (int) $job->getAttribute('applications_count'),
                'joined' => (int) $job->getAttribute('joined_count'),
            ])->values()->all(),
            team: $project->members()->orderBy('name')->get()
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->values()->all(),
        );
    }

    /**
     * The project's applicants across its jobs, for bulk shortlisting.
     *
     * @return Builder<Application>
     */
    public static function applicants(RecruitmentProject $project): Builder
    {
        return Application::query()->whereIn('job_id', $project->jobs()->select('id'));
    }
}
