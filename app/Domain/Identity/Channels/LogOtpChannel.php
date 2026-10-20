<?php

namespace App\Domain\Identity\Channels;

use App\Domain\Identity\Contracts\OtpChannel;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Local development channel: writes the code to the application log.
 */
class LogOtpChannel implements OtpChannel
{
    public function name(): string
    {
        return 'log';
    }

    public function send(string $phone, string $code, string $locale): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The log OTP channel must not be used in production.');
        }

        Log::info("BLUESKILLS sign-in code for {$phone}: {$code}");
    }
}
