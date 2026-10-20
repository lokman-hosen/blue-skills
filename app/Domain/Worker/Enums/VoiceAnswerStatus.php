<?php

namespace App\Domain\Worker\Enums;

enum VoiceAnswerStatus: string
{
    case Pending = 'pending';
    case Transcribed = 'transcribed';
    case Extracted = 'extracted';
    case Confirmed = 'confirmed';
    case Discarded = 'discarded';
    /** Speech could not be transcribed; `error_code` says why and the worker retries or types. */
    case Failed = 'failed';

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
            self::Failed => 'Failed',
        };
    }

    /**
     * Still waiting for speech-to-text or extraction.
     */
    public function isProcessing(): bool
    {
        return $this === self::Pending || $this === self::Transcribed;
    }
}
