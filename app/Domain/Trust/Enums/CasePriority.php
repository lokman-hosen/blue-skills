<?php

namespace App\Domain\Trust\Enums;

enum CasePriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'neutral',
            self::Normal => 'brand',
            self::High => 'warn',
            self::Urgent => 'bad',
        };
    }
}
