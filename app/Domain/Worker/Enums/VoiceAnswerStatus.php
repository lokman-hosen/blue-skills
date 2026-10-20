<?php

namespace App\Domain\Worker\Enums;

enum VoiceAnswerStatus: string
{
    case Pending = 'pending';
    case Transcribed = 'transcribed';
    case Extracted = 'extracted';
    case Confirmed = 'confirmed';
    case Discarded = 'discarded';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Transcribed => 'Transcribed',
            self::Extracted => 'Extracted',
            self::Confirmed => 'Confirmed',
            self::Discarded => 'Discarded',
        };
    }
}
