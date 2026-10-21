<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Rejected;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Jobs\Support\JobWizard;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Organization\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves one wizard step. A material change to a live job sends it back to review
 * (product rule 5); applicants are notified once applications exist (Modules 4–5).
 */
class SaveJobStep
{
    public function __construct(private readonly SubmitJobForReview $submit) {}

    /**
     * @param  array<string, mixed>  $validated  Validated with JobWizard::rules($step).
     */
    public function handle(Organization $organization, ?JobPosting $job, string $step, array $validated, User $actor): JobPosting
    {
        $data = JobWizard::normalise($step, $validated);

        return DB::transaction(function () use ($organization, $job, $step, $data, $actor): JobPosting {
            if ($job === null) {
                $job = JobPosting::query()->create([
                    ...$data['attributes'],
                    'organization_id' => $organization->id,
                    'employer_organization_id' => $organization->id,
                    'public_code' => JobWizard::code((string) $data['attributes']['destination_country_code'], (string) $data['attributes']['occupation_id']),
                    'status' => Draft::class,
                ]);

                activity('jobs')->performedOn($job)->causedBy($actor)->log('job.created');

                return $job;
            }

            $wasLive = $job->isLive();
            $costsBefore = $this->costSignature($job);

            if ($job->status->equals(Rejected::class)) {
                $job->status->transitionTo(Draft::class);
            }

            $job->fill($data['attributes']);
            $materialFields = array_values(array_intersect(array_keys($job->getDirty()), JobPosting::MATERIAL_FIELDS));
            $job->save();

            if (array_key_exists('skill_ids', $data)) {
                $job->skills()->sync(collect($data['skill_ids'])->mapWithKeys(fn (string $id) => [$id => ['id' => (string) str()->ulid(), 'required' => true]])->all());
            }

            if (array_key_exists('costs', $data)) {
                $job->costs()->delete();
                $job->costs()->createMany($data['costs']);
                $job->unsetRelation('costs');
            }

            $costsChanged = array_key_exists('costs', $data) && $costsBefore !== $this->costSignature($job);

            if ($wasLive && ($materialFields !== [] || $costsChanged)) {
                $changed = [...$materialFields, ...($costsChanged ? ['costs'] : [])];
                $problems = JobCompliance::problems($job);

                if ($problems !== []) {
                    throw ValidationException::withMessages($problems);
                }

                $job->forceFill(['material_change_at' => now()])->save();
                $this->submit->handle($job, $actor, materialChanges: $changed);

                // Product rule 5: everyone still in the process hears about it.
                $job->applications()->with(['workerProfile.user', 'job.organization'])->get()
                    ->reject(fn (Application $application) => $application->isClosed())
                    ->each(fn (Application $application) => Notify::worker($application, NotificationEvent::JobChanged));
            }

            activity('jobs')->performedOn($job)->causedBy($actor)
                ->withProperties(['step' => $step])
                ->log('job.step_saved');

            return $job;
        });
    }

    /**
     * @return list<string>
     */
    private function costSignature(JobPosting $job): array
    {
        return $job->costs()->get()
            ->map(fn (JobCost $cost) => implode('|', [$cost->category->value, $cost->amount_minor, $cost->currency, $cost->payer->value]))
            ->sort()
            ->values()
            ->all();
    }
}
