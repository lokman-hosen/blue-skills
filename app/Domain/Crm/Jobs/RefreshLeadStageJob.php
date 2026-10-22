<?php

namespace App\Domain\Crm\Jobs;

use App\Domain\Crm\Actions\RefreshLeadStage;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Brings a worker's lead stage up to date after their profile, an application or a mobility case
 * changed. Unique per worker while queued, so a burst of changes refreshes once.
 */
class RefreshLeadStageJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public string $userId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->userId;
    }

    public function handle(RefreshLeadStage $refresh): void
    {
        $user = User::query()->find($this->userId);

        if ($user !== null) {
            $refresh->handle($user);
        }
    }
}
