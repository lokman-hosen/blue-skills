<?php

namespace App\Domain\Messaging\Channels;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Jobs\DeliverOutboundMessage;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Messaging\Notifications\AppNotification;
use App\Domain\Messaging\Support\Preferences;
use App\Domain\Messaging\Support\QuietHours;
use Carbon\CarbonImmutable;

/**
 * Laravel notification channel for alerts outside the app. Workers: WhatsApp, or SMS when
 * WhatsApp is off for the event (and as fallback when WhatsApp fails). Organization members:
 * email. Every attempt is an `outbound_messages` row; night-time worker alerts are held.
 */
class ExternalChannel
{
    public function send(object $notifiable, AppNotification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        $event = $notification->event;
        $payload = ['params' => $notification->params, 'url' => $notification->url];

        if ($event->forWorkers()) {
            $phone = (string) $notifiable->phone;
            $channel = match (true) {
                Preferences::enabled($notifiable, $event, DeliveryChannel::WhatsApp) => DeliveryChannel::WhatsApp,
                Preferences::enabled($notifiable, $event, DeliveryChannel::Sms) => DeliveryChannel::Sms,
                default => null,
            };

            if ($channel === null || ! $notifiable->isWorker() || $phone === '') {
                return;
            }

            $release = QuietHours::releaseAt($notifiable);
            self::queue($notifiable, $channel, $phone, $event, $payload, $release === null ? DeliveryStatus::Queued : DeliveryStatus::Held, $release);

            return;
        }

        if ($notifiable->isWorker() || blank($notifiable->email) || ! Preferences::enabled($notifiable, $event, DeliveryChannel::Mail)) {
            return;
        }

        self::queue($notifiable, DeliveryChannel::Mail, (string) $notifiable->email, $event, $payload, DeliveryStatus::Queued, null);
    }

    /**
     * @param  array{params: array<string, string|int>, url: string}  $payload
     */
    public static function queue(User $user, DeliveryChannel $channel, string $recipient, NotificationEvent $event, array $payload, DeliveryStatus $status, ?CarbonImmutable $sendAfter): OutboundMessage
    {
        $message = OutboundMessage::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'event' => $event,
            'template' => $channel === DeliveryChannel::WhatsApp ? (string) config('services.whatsapp.update_template') : null,
            'payload' => $payload,
            'status' => $status,
            'send_after' => $sendAfter,
        ]);

        if ($status === DeliveryStatus::Queued) {
            DeliverOutboundMessage::dispatch($message->id);
        }

        return $message;
    }
}
