<?php

namespace App\Domain\Crm\Enums;

enum ContactChannel: string
{
    case PhoneCall = 'phone_call';
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case Email = 'email';
    case InPerson = 'in_person';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::PhoneCall => 'Phone call',
            self::WhatsApp => 'WhatsApp',
            self::Sms => 'SMS',
            self::Email => 'Email',
            self::InPerson => 'In person',
        };
    }
}
