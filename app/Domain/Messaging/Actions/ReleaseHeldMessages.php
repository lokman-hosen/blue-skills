<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Jobs\DeliverOutboundMessage;
use App\Domain\Messaging\Models\OutboundMessage;

/**
 * Sends alerts held during a worker's quiet hours once those hours are over.
 */
class ReleaseHeldMessages
{
    /**
     * @return int Messages released.
     */
    public function handle(): int
    {
        $released = 0;

        OutboundMessage::query()
            ->where('status', DeliveryStatus::Held)
            ->where('send_after', '<=', now())
            ->orderBy('send_after')
            ->limit(1000)
            ->get()
            ->each(function (OutboundMessage $message) use (&$released): void {
                $message->update(['status' => DeliveryStatus::Queued]);
                DeliverOutboundMessage::dispatch($message->id);
                $released++;
            });

        return $released;
    }
}
