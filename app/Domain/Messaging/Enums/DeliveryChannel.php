<?php

namespace App\Domain\Messaging\Enums;

/**
 * Channels outside the app. In-app notifications are always on and are not a preference.
 */
enum DeliveryChannel: string
{
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case Mail = 'mail';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Sms => 'SMS',
            self::Mail => 'Email',
        };
    }
}
