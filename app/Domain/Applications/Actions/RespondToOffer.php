<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\Declined;
use App\Domain\Applications\Support\StageChange;
use App\Domain\Mobility\Actions\OpenMobilityCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The worker accepts or declines the open offer. Accepting is the milestone that opens the
 * mobility case (product rule 8).
 */
class RespondToOffer
{
    public function __construct(private readonly OpenMobilityCase $open) {}

    public function handle(Application $application, bool $accept): void
    {
        $offer = $application->openOffer();

        if ($offer === null) {
            throw ValidationException::withMessages(['offer' => __('applications.offer.none_open')]);
        }

        DB::transaction(function () use ($application, $offer, $accept): void {
            $offer->update(['status' => $accept ? OfferStatus::Accepted : OfferStatus::Declined, 'responded_at' => now()]);
            StageChange::apply($application, $accept ? Accepted::class : Declined::class, $application->workerProfile->user);

            if ($accept) {
                $this->open->handle($application, $application->workerProfile->user);
            }
        });
    }
}
