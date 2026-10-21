<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Applications\Support\StageChange;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sends offer terms and moves the application to "Offer". Pay is in the job's currency and
 * period and can never be lower than the Truth Card promised (product rule 4).
 */
class SendOffer
{
    /**
     * @param  array{salary_amount_minor: int, start_on: ?string, respond_by: ?string, note: ?string}  $terms
     */
    public function handle(Application $application, array $terms, User $actor): Offer
    {
        $job = $application->job;

        if ($application->openOffer() !== null) {
            throw ValidationException::withMessages(['salary_amount' => __('applications.offer.already_open')]);
        }

        if (! $application->status->canTransitionTo(OfferStage::class)) {
            throw ValidationException::withMessages(['salary_amount' => __('applications.stage.not_allowed')]);
        }

        if ($job->salary_currency === null || $job->salary_period === null) {
            throw ValidationException::withMessages(['salary_amount' => __('applications.offer.job_salary_missing')]);
        }

        if ($job->salary_amount_minor !== null && $terms['salary_amount_minor'] < $job->salary_amount_minor) {
            throw ValidationException::withMessages(['salary_amount' => __('applications.offer.below_truth_card')]);
        }

        return DB::transaction(function () use ($application, $job, $terms, $actor): Offer {
            /** @var Offer $offer */
            $offer = $application->offers()->create([
                ...$terms,
                'salary_currency' => $job->salary_currency,
                'salary_period' => $job->salary_period,
                'status' => OfferStatus::Sent,
                'sent_by' => $actor->id,
            ]);

            StageChange::apply($application, OfferStage::class, $actor);

            activity('applications')->performedOn($application)->causedBy($actor)
                ->withProperties(['offer' => $offer->id, 'salary_amount_minor' => $offer->salary_amount_minor, 'currency' => $offer->salary_currency])
                ->log('offer.sent');

            return $offer;
        });
    }
}
