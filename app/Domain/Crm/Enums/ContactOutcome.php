<?php

namespace App\Domain\Crm\Enums;

enum ContactOutcome: string
{
    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case WrongNumber = 'wrong_number';
    case CallBack = 'call_back';
    case NotInterested = 'not_interested';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reached => 'Reached',
            self::NoAnswer => 'No answer',
            self::WrongNumber => 'Wrong number',
            self::CallBack => 'Asked to call back',
            self::NotInterested => 'Not interested',
        };
    }
}
