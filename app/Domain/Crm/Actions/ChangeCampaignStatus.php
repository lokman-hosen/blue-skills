<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\States\Campaign\Active;
use App\Domain\Crm\States\Campaign\Ended;
use App\Domain\Crm\States\Campaign\Paused;
use Illuminate\Validation\ValidationException;

/**
 * Pause, resume or end a campaign. Ending is final; its link still works but counts nothing.
 */
class ChangeCampaignStatus
{
    public const ACTIONS = ['pause', 'resume', 'end'];

    public function handle(Campaign $campaign, string $action): void
    {
        $target = match ($action) {
            'pause' => Paused::class,
            'resume' => Active::class,
            default => Ended::class,
        };

        if (! $campaign->status->canTransitionTo($target)) {
            throw ValidationException::withMessages(['status' => __('marketing.errors.status')]);
        }

        $campaign->status->transitionTo($target);

        if ($target === Ended::class && ($campaign->ends_on === null || $campaign->ends_on->isFuture())) {
            $campaign->update(['ends_on' => now()->toDateString()]);
        }
    }
}
