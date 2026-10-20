<?php

namespace App\Domain\Worker\Exceptions;

use RuntimeException;

class TranscriptionFailed extends RuntimeException
{
    /** No provider configured. */
    public const UNAVAILABLE = 'unavailable';

    /** Silence, noise or speech the provider could not read. */
    public const NO_SPEECH = 'no_speech';

    /** The provider failed or timed out. */
    public const PROVIDER_ERROR = 'provider_error';

    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : "Transcription failed: {$reason}");
    }
}
