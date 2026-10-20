<?php

namespace App\Domain\Worker\Enums;

enum LanguageProficiency: string
{
    case Basic = 'basic';
    case Conversational = 'conversational';
    case Fluent = 'fluent';
    case Native = 'native';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Conversational => 'Conversational',
            self::Fluent => 'Fluent',
            self::Native => 'Native',
        };
    }
}
