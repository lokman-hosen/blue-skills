<?php

namespace App\Domain\Messaging\Support;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Models\NotificationPreference;

/**
 * Channel choices per event. Everything is on until the person switches it off.
 */
final class Preferences
{
    public static function enabled(User $user, NotificationEvent $event, DeliveryChannel $channel): bool
    {
        return NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event', $event)
            ->where('channel', $channel)
            ->value('enabled') ?? true;
    }

    /**
     * @return list<NotificationEvent>
     */
    public static function eventsFor(User $user): array
    {
        return $user->isWorker() ? NotificationEvent::workerEvents() : NotificationEvent::organizationEvents();
    }
}
