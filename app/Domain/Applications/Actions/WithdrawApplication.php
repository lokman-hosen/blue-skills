<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Withdrawn;
use App\Domain\Applications\Support\StageChange;
use Illuminate\Support\Facades\DB;

/**
 * The worker withdraws. An open offer is withdrawn with it. The snapshot stays with the
 * application (product rule 7); shared items stay as they are until the worker revokes them.
 */
class WithdrawApplication
{
    public function handle(Application $application): void
    {
        DB::transaction(function () use ($application): void {
            $application->offers()->where('status', OfferStatus::Sent)->update(['status' => OfferStatus::Withdrawn, 'responded_at' => now()]);
            StageChange::apply($application, Withdrawn::class, $application->workerProfile->user);
        });
    }
}
