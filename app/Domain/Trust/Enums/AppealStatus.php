<?php

namespace App\Domain\Trust\Enums;

enum AppealStatus: string
{
    case Submitted = 'submitted';
    case Upheld = 'upheld';
    case Overturned = 'overturned';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Waiting for a decision',
            self::Upheld => 'Suspension upheld',
            self::Overturned => 'Suspension lifted',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'warn',
            self::Upheld => 'bad',
            self::Overturned => 'ok',
        };
    }
}
