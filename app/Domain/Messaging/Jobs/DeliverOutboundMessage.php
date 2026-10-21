<?php

namespace App\Domain\Messaging\Jobs;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Channels\ExternalChannel;
use App\Domain\Messaging\Contracts\SmsSender;
use App\Domain\Messaging\Contracts\WhatsAppSender;
use App\Domain\Messaging\Data\NotificationData;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Mail\NotificationMail;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Messaging\Support\Preferences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use Throwable;

/**
 * Sends one WhatsApp, SMS or email alert in the recipient's language and records the outcome.
 * A failed WhatsApp alert falls back to SMS when the worker allows SMS for the event. Failures
 * are recorded, not retried, so one provider outage never floods a worker later.
 */
class DeliverOutboundMessage implements ShouldQueue
{
    use Localizable, Queueable;

    public int $tries = 1;

    public function __construct(public string $outboundMessageId) {}

    public function handle(WhatsAppSender $whatsApp, SmsSender $sms): void
    {
        $message = OutboundMessage::query()->with('user')->find($this->outboundMessageId);

        if ($message === null || ! in_array($message->status, [DeliveryStatus::Queued, DeliveryStatus::Held], true) || $message->user === null) {
            return;
        }

        $user = $message->user;

        try {
            $providerId = $this->withLocale($user->locale ?: 'en', fn (): string => $this->send($message, $user, $whatsApp, $sms));
            $message->update(['status' => DeliveryStatus::Sent, 'provider_id' => $providerId, 'sent_at' => now(), 'attempts' => $message->attempts + 1]);
        } catch (Throwable $exception) {
            report($exception);
            $message->update(['status' => DeliveryStatus::Failed, 'error' => Str::limit($exception->getMessage(), 500), 'attempts' => $message->attempts + 1]);

            if ($message->channel === DeliveryChannel::WhatsApp && Preferences::enabled($user, $message->event, DeliveryChannel::Sms)) {
                ExternalChannel::queue($user, DeliveryChannel::Sms, $message->recipient, $message->event, $message->payload, DeliveryStatus::Queued, null);
            }
        }
    }

    private function send(OutboundMessage $message, User $user, WhatsAppSender $whatsApp, SmsSender $sms): string
    {
        $event = $message->event->value;
        $params = NotificationData::params($message->payload['params'], $user);
        $title = __("notification.{$event}.title", $params);
        $link = url($message->payload['url']);

        return match ($message->channel) {
            DeliveryChannel::WhatsApp => $whatsApp->sendTemplate(
                $message->recipient,
                (string) $message->template,
                $user->locale ?: 'en',
                [$params['job'] ?? $title, $link],
            ),
            DeliveryChannel::Sms => $sms->send($message->recipient, __('notification.sms', ['title' => $title, 'link' => $link])),
            DeliveryChannel::Mail => $this->mail($message, $user, $title, __("notification.{$event}.body", $params), $link),
        };
    }

    private function mail(OutboundMessage $message, User $user, string $title, string $body, string $link): string
    {
        Mail::to($message->recipient)->locale($user->locale ?: 'en')->send(new NotificationMail($title, $body, $link));

        return 'mail-'.$message->id;
    }
}
