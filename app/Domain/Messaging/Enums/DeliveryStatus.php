<?php

namespace App\Domain\Messaging\Enums;

/**
 * One delivery attempt outside the app. `held` waits for the worker's quiet hours to end;
 * `skipped` means the preference was off or the user has no address for the channel.
 */
enum DeliveryStatus: string
{
    case Held = 'held';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
