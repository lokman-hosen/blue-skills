<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Enums\RejectionReason;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Applications\Support\StageChange;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ends an application with a reason code the worker sees. The note stays internal.
 */
class RejectApplication
{
    public function handle(Application $application, RejectionReason $reason, ?string $note, User $actor): void
    {
        DB::transaction(function () use ($application, $reason, $note, $actor): void {
            $application->offers()->where('status', OfferStatus::Sent)->update(['status' => OfferStatus::Withdrawn, 'responded_at' => now()]);
            StageChange::apply($application, Rejected::class, $actor, $reason->value, $note);
        });
    }
}
