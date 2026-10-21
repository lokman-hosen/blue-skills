<?php

namespace App\Domain\Messaging\Data;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\LaravelData\Data;

/**
 * A notification rendered in the reader's language and time zone.
 */
class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public NotificationEvent $event,
        public string $title,
        public string $body,
        public string $url,
        public bool $read,
        public string $createdAt,
    ) {}

    public static function fromNotification(DatabaseNotification $notification, User $reader): self
    {
        /** @var array{event: string, params: array<string, string|int>, url: string} $data */
        $data = $notification->data;
        $event = NotificationEvent::from($data['event']);

        return new self(
            id: $notification->id,
            event: $event,
            title: __("notification.{$event->value}.title", self::params($data['params'], $reader)),
            body: self::body($event, $data['params'], $reader),
            url: $data['url'],
            read: $notification->read_at !== null,
            createdAt: $notification->created_at?->toIso8601String() ?? '',
        );
    }

    /**
     * Counts pick the right plural form.
     *
     * @param  array<string, string|int>  $params
     */
    public static function body(NotificationEvent $event, array $params, User $reader): string
    {
        $key = "notification.{$event->value}.body";

        return array_key_exists('count', $params)
            ? trans_choice($key, (int) $params['count'], self::params($params, $reader))
            : __($key, self::params($params, $reader));
    }

    /**
     * Codes become words and times move to the reader's time zone.
     *
     * @param  array<string, string|int>  $params
     * @return array<string, string>
     */
    public static function params(array $params, User $reader): array
    {
        $rendered = [];

        foreach ($params as $key => $value) {
            $rendered[$key] = match ($key) {
                'reason' => __("rejection_reason.{$value}"),
                'answer' => __("notification.answer.{$value}"),
                'decision' => __("review_decision.{$value}"),
                'mandate_answer' => __("notification.mandate_answer.{$value}"),
                'case_action' => __("notification.case_action.{$value}"),
                'document_action' => __("notification.document_action.{$value}"),
                'record_action' => __("notification.record_action.{$value}"),
                'evidence_type' => __("evidence.type.{$value}"),
                'closed_reason' => __("mobility.closed_reason.{$value}"),
                'at' => CarbonImmutable::parse((string) $value)
                    ->setTimezone($reader->timezone ?: 'UTC')
                    ->locale(app()->getLocale())
                    ->isoFormat('LLL'),
                default => (string) $value,
            };
        }

        return $rendered;
    }
}
