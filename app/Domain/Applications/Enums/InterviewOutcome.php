<?php

namespace App\Domain\Applications\Enums;

enum InterviewOutcome: string
{
    case Passed = 'passed';
    case NotPassed = 'not_passed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Passed',
            self::NotPassed => 'Not passed',
            self::NoShow => 'Did not attend',
            self::Cancelled => 'Cancelled',
        };
    }
}
