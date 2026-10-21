<?php

namespace App\Domain\Messaging\Notifications;

use App\Domain\Messaging\Channels\ExternalChannel;
use App\Domain\Messaging\Enums\NotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Every BLUESKILLS alert (spec §9). The stored data is the event, its parameters and a link;
 * the text is rendered in the reader's language and time zone when shown or sent.
 */
class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string|int>  $params
     */
    public function __construct(
        public NotificationEvent $event,
        public array $params,
        public string $url,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', ExternalChannel::class];
    }

    /**
     * @return array{event: string, params: array<string, string|int>, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return ['event' => $this->event->value, 'params' => $this->params, 'url' => $this->url];
    }

    /**
     * Ids only; the page reloads what the reader may see.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(['event' => $this->event->value]);
    }

    public function databaseType(object $notifiable): string
    {
        return $this->event->value;
    }
}
