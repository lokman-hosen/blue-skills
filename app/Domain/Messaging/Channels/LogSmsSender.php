<?php

namespace App\Domain\Messaging\Channels;

use App\Domain\Messaging\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes SMS alerts to the log locally and in tests.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $text): string
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The log SMS driver must not be used in production.');
        }

        Log::info("BLUESKILLS SMS to {$phone}: {$text}");

        return 'log-'.Str::ulid();
    }
}
