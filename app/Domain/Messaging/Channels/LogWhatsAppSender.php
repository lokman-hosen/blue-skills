<?php

namespace App\Domain\Messaging\Channels;

use App\Domain\Messaging\Contracts\WhatsAppSender;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes WhatsApp alerts to the log until the business account exists (D-067).
 */
class LogWhatsAppSender implements WhatsAppSender
{
    public function sendTemplate(string $phone, string $template, string $locale, array $parameters): string
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The log WhatsApp driver must not be used in production.');
        }

        Log::info("BLUESKILLS WhatsApp {$template} ({$locale}) to {$phone}: ".implode(' | ', $parameters));

        return 'log-'.Str::ulid();
    }
}
