<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Data\ProfileSnapshotData;
use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Applications\Support\ShareableItems;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "I can do this job": one application per worker per job, with a dated profile snapshot and
 * explicit per-item consent (product rules 6 and 7, spec §6).
 */
class CreateApplication
{
    /**
     * @param  list<ApplicationConsentItem>  $items  What the worker chose to share.
     */
    public function handle(WorkerProfile $profile, JobPosting $job, array $items): Application
    {
        if (! $job->acceptsApplications()) {
            throw ValidationException::withMessages(['job' => __('applications.apply.closed')]);
        }

        if ($profile->occupation_id === null) {
            throw ValidationException::withMessages(['job' => __('applications.apply.needs_trade')]);
        }

        if ($profile->applications()->where('job_id', $job->id)->exists()) {
            throw ValidationException::withMessages(['job' => __('applications.apply.duplicate')]);
        }

        $available = ShareableItems::for($profile);
        $shared = array_values(array_filter(
            ApplicationConsentItem::cases(),
            fn (ApplicationConsentItem $item) => $available[$item->value]->required
                || ($available[$item->value]->available && in_array($item, $items, true)),
        ));

        try {
            return DB::transaction(fn () => $this->store($profile, $job, $shared));
        } catch (UniqueConstraintViolationException) {
            // A double tap that got past the check above.
            throw ValidationException::withMessages(['job' => __('applications.apply.duplicate')]);
        }
    }

    /**
     * @param  list<ApplicationConsentItem>  $shared
     */
    private function store(WorkerProfile $profile, JobPosting $job, array $shared): Application
    {
        $now = now();
        $skillShow = in_array(ApplicationConsentItem::SkillShow, $shared, true) ? ShareableItems::skillShow($profile) : null;
        $scan = in_array(ApplicationConsentItem::PassportScan, $shared, true) ? ShareableItems::passportScan($profile) : null;

        $application = Application::query()->create([
            'job_id' => $job->id,
            'worker_profile_id' => $profile->id,
            'status' => Applied::class,
            'applied_at' => $now,
            'stage_changed_at' => $now,
            'skill_show_id' => $skillShow?->id,
        ]);

        $application->snapshot()->create([
            'data' => ProfileSnapshotData::capture($profile, $job, $shared)->toArray(),
            'taken_at' => $now,
        ]);

        foreach (ApplicationConsentItem::cases() as $item) {
            $granted = in_array($item, $shared, true);
            $application->consents()->create([
                'item' => $item,
                'granted' => $granted,
                'granted_at' => $granted ? $now : null,
                'document_id' => $item === ApplicationConsentItem::PassportScan ? $scan?->id : null,
            ]);
        }

        $profile->consents()->create([
            'purpose' => ConsentPurpose::ApplicationShare,
            'subject_type' => $application->getMorphClass(),
            'subject_id' => $application->id,
            'scope' => ['job' => $job->public_code, 'items' => array_map(fn (ApplicationConsentItem $item) => $item->value, $shared)],
            'granted_at' => $now,
        ]);

        if ($scan !== null) {
            $profile->consents()->create([
                'purpose' => ConsentPurpose::DocumentShare,
                'subject_type' => $application->getMorphClass(),
                'subject_id' => $application->id,
                'scope' => ['item' => ApplicationConsentItem::PassportScan->value, 'document' => $scan->id],
                'granted_at' => $now,
            ]);
        }

        $application->stageEvents()->create([
            'from_status' => null,
            'to_status' => Applied::getMorphClass(),
            'actor_user_id' => $profile->user_id,
        ]);

        activity('applications')->performedOn($application)->causedBy($profile->user)
            ->withProperties(['job' => $job->public_code, 'shared' => array_map(fn (ApplicationConsentItem $item) => $item->value, $shared)])
            ->log('application.created');

        return $application;
    }
}
