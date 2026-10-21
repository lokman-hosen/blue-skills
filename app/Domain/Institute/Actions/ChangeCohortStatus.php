<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Institute\Models\Cohort;

/**
 * Closing a cohort stops new graduates joining through its link; graduates who joined stay.
 */
class ChangeCohortStatus
{
    public function handle(Cohort $cohort, CohortStatus $status, User $actor): void
    {
        if ($cohort->status === $status) {
            return;
        }

        $old = $cohort->status;
        $cohort->update(['status' => $status]);

        activity('institute')->performedOn($cohort)->causedBy($actor)
            ->withProperties(['old' => ['status' => $old->value], 'attributes' => ['status' => $status->value]])
            ->log('cohort.'.($status === CohortStatus::Open ? 'opened' : 'closed'));
    }
}
