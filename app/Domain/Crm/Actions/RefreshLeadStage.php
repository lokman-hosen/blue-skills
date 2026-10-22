<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Support\LeadProgress;
use App\Domain\Identity\Models\User;

/**
 * Moves a worker's lead to the furthest stage they have reached. Stages only move forward: a
 * withdrawn application doesn't undo "applied" (D-095).
 */
class RefreshLeadStage
{
    public function handle(User $worker): void
    {
        $lead = $worker->lead()->first();

        if ($lead === null) {
            return;
        }

        $stage = LeadProgress::stageFor($worker);

        if ($stage->rank() > $lead->conversion_stage->rank()) {
            $lead->update(['conversion_stage' => $stage, 'stage_reached_at' => now()]);
        }
    }
}
