<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Open;
use App\Domain\Mobility\Support\StageTemplate;
use App\Domain\Organization\Support\OrganizationPermissions;
use Illuminate\Support\Facades\DB;

/**
 * Opens the mobility case when the worker accepts an offer (product rule 8): a separate record
 * from the application, built from the destination's stage template. The organization that
 * posted the job runs it (the agency for agency jobs, D-074).
 */
class OpenMobilityCase
{
    public function handle(Application $application, User $actor): MobilityCase
    {
        $application->loadMissing(['job.organization', 'assignee', 'mobilityCase']);

        if ($application->mobilityCase !== null) {
            return $application->mobilityCase;
        }

        $job = $application->job;
        $template = StageTemplate::for($job->destination_country_code);

        return DB::transaction(function () use ($application, $job, $template, $actor): MobilityCase {
            $case = MobilityCase::query()->create([
                'application_id' => $application->id,
                'template' => $job->destination_country_code,
                'reference' => self::newReference($job->destination_country_code),
                'status' => Open::class,
                'managing_organization_id' => $job->organization_id,
                'employer_organization_id' => $job->employer_organization_id,
                'agency_organization_id' => $job->agency_organization_id,
                'handler_user_id' => $this->handler($application),
            ]);

            $case->events()->create(['action' => CaseEventAction::Opened, 'actor_user_id' => $actor->id]);
            $current = null;

            foreach ($template as $position => $stage) {
                $doneOnOpen = ($stage['done_on_open'] ?? false) === true && $current === null;
                $status = match (true) {
                    $doneOnOpen => MobilityStageStatus::Done,
                    $current === null => MobilityStageStatus::InProgress,
                    default => MobilityStageStatus::Pending,
                };

                $case->stages()->create([
                    'key' => $stage['key'],
                    'position' => $position + 1,
                    'status' => $status,
                    'completed_at' => $doneOnOpen ? now() : null,
                ]);

                if ($doneOnOpen) {
                    $case->events()->create(['action' => CaseEventAction::StageCompleted, 'stage_key' => $stage['key'], 'actor_user_id' => $actor->id]);
                } elseif ($current === null) {
                    $current = $stage['key'];
                    $case->events()->create(['action' => CaseEventAction::StageStarted, 'stage_key' => $current, 'actor_user_id' => $actor->id]);
                }
            }

            $case->update(['current_stage' => $current]);

            activity('mobility')->performedOn($case)->causedBy($actor)
                ->withProperties(['application' => $application->id, 'template' => $case->template, 'managed_by' => $case->managing_organization_id])
                ->log('mobility_case.opened');

            Notify::worker($application, NotificationEvent::CaseOpened, ['reference' => $case->reference], route('worker.cases.show', $case, false));

            return $case;
        });
    }

    /**
     * "Handled by": the application's assignee, else whoever sent the accepted offer, if they
     * still belong to the posting organization.
     */
    private function handler(Application $application): ?string
    {
        $organization = $application->job->organization;
        $candidates = [$application->assignee, $application->offers()->latest()->first()?->sender];

        foreach ($candidates as $user) {
            if ($user !== null && OrganizationPermissions::isMember($user, $organization)) {
                return $user->id;
            }
        }

        return null;
    }

    /**
     * MC-AE-2026-0188: destination, year and four random digits.
     */
    private static function newReference(string $countryCode): string
    {
        do {
            $reference = 'MC-'.$countryCode.'-'.now()->year.'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (MobilityCase::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
