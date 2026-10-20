<?php

namespace App\Domain\Worker\Enums;

enum SkillSource: string
{
    case Voice = 'voice';
    case Typed = 'typed';
    case Institute = 'institute';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Voice => 'Voice',
            self::Typed => 'Typed',
            self::Institute => 'Institute',
        };
    }
}
