<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or edits a recruitment project: name, target headcount, countries, its jobs and team
 * (spec §6). A job belongs to one project at a time; a job already in another project is refused
 * rather than silently moved.
 */
class SaveRecruitmentProject
{
    /**
     * @param  array{name: string, target_headcount: int, destination_country_code: ?string, source_country_codes: list<string>, target_date: ?string}  $attributes
     * @param  list<string>  $jobIds
     * @param  list<string>  $memberIds
     */
    public function handle(Organization $organization, ?RecruitmentProject $project, array $attributes, array $jobIds, array $memberIds, User $actor): RecruitmentProject
    {
        $jobs = JobPosting::query()->where('organization_id', $organization->id)->whereIn('id', $jobIds)->get();

        if ($jobs->count() !== count(array_unique($jobIds))) {
            throw ValidationException::withMessages(['job_ids' => __('projects.errors.job_not_found')]);
        }

        $taken = $jobs->first(fn (JobPosting $job) => $job->recruitment_project_id !== null && $job->recruitment_project_id !== $project?->id);

        if ($taken !== null) {
            throw ValidationException::withMessages(['job_ids' => __('projects.errors.job_taken', ['job' => $taken->title])]);
        }

        $members = $organization->users()->whereIn('users.id', $memberIds)->pluck('users.id')->all();

        return DB::transaction(function () use ($organization, $project, $attributes, $jobIds, $members, $actor): RecruitmentProject {
            if ($project === null) {
                $project = $organization->recruitmentProjects()->create([
                    ...$attributes,
                    'reference' => RecruitmentProject::newReference($organization->id),
                    'created_by' => $actor->id,
                ]);
                activity('jobs')->performedOn($project)->causedBy($actor)->log('project.created');
            } else {
                $project->update($attributes);
            }

            $project->jobs()->whereNotIn('id', $jobIds)->update(['recruitment_project_id' => null]);
            JobPosting::query()->whereIn('id', $jobIds)->update(['recruitment_project_id' => $project->id]);
            $project->members()->sync($members);

            return $project;
        });
    }
}
