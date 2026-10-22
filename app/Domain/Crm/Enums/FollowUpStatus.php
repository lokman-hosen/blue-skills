<?php

namespace App\Domain\Crm\Enums;

/**
 * Set by the CRM agent; do-not-contact is also set when the worker opts out (D-095).
 */
enum FollowUpStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case NoAnswer = 'no_answer';
    case CallBack = 'call_back';
    case NotInterested = 'not_interested';
    case DoNotContact = 'do_not_contact';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::NoAnswer => 'No answer',
            self::CallBack => 'Call back',
            self::NotInterested => 'Not interested',
            self::DoNotContact => 'Do not contact',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'brand',
            self::Contacted => 'ok',
            self::NoAnswer => 'warn',
            self::CallBack => 'stamp',
            self::NotInterested => 'neutral',
            self::DoNotContact => 'bad',
        };
    }
}
