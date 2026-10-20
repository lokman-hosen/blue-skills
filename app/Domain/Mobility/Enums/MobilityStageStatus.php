<?php

namespace App\Domain\Mobility\Enums;

enum MobilityStageStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Blocked = 'blocked';
    case Skipped = 'skipped';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In progress',
            self::Done => 'Done',
            self::Blocked => 'Blocked',
            self::Skipped => 'Skipped',
        };
    }
}
