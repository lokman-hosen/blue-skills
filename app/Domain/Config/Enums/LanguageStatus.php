<?php

namespace App\Domain\Config\Enums;

enum LanguageStatus: string
{
    case Active = 'active';
    case InReview = 'in_review';
    case Planned = 'planned';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::InReview => 'In review',
            self::Planned => 'Planned',
        };
    }
}
