<?php

namespace App\Domain\Messaging\Data;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Preferences;
use Spatie\LaravelData\Data;

class PreferenceRowData extends Data
{
    /**
     * @param  array<string, bool>  $channels  channel => enabled
     */
    public function __construct(
        public NotificationEvent $event,
        public array $channels,
    ) {}

    public static function forUser(User $user, NotificationEvent $event): self
    {
        $channels = [];

        foreach ($event->channels() as $channel) {
            /** @var DeliveryChannel $channel */
            $channels[$channel->value] = Preferences::enabled($user, $event, $channel);
        }

        return new self($event, $channels);
    }
}
