<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\Expired;
use App\Domain\Verification\States\Evidence\IssuerVerified;

/**
 * Daily (D-090): verified evidence whose document has passed its expiry date becomes expired
 * (product rule 9). The worker uploads a new one to be checked again.
 */
class ExpireEvidence
{
    public function handle(): int
    {
        $expired = 0;

        EvidenceItem::query()
            ->whereState('status', IssuerVerified::class)
            ->where('documentable_type', (new Document)->getMorphClass())
            ->whereHasMorph('documentable', [Document::class], fn ($documents) => $documents->whereDate('expires_at', '<', now()->toDateString()))
            ->get()
            ->each(function (EvidenceItem $item) use (&$expired): void {
                $item->status->transitionTo(Expired::class);
                activity('verification')->performedOn($item)->log('evidence.expired');
                $expired++;
            });

        return $expired;
    }
}
