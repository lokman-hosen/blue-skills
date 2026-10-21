<?php

namespace App\Domain\Messaging\Channels;

use App\Domain\Messaging\Contracts\WhatsAppSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * WhatsApp Cloud API (Meta). Template messages only, since alerts start the conversation.
 */
class CloudWhatsAppSender implements WhatsAppSender
{
    /**
     * Languages the update template is approved in (D-067); anything else gets English.
     */
    private const TEMPLATE_LANGUAGES = ['en' => 'en', 'bn' => 'bn', 'hi' => 'hi', 'ur' => 'ur', 'ne' => 'ne'];

    public function sendTemplate(string $phone, string $template, string $locale, array $parameters): string
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (blank($token) || blank($phoneNumberId)) {
            throw new RuntimeException('WhatsApp is not configured: set WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID.');
        }

        $version = config('services.whatsapp.api_version');

        $response = Http::withToken((string) $token)
            ->acceptJson()
            ->timeout(10)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($phone, '+'),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => self::TEMPLATE_LANGUAGES[$locale] ?? 'en'],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(fn (string $text) => ['type' => 'text', 'text' => $text], $parameters),
                    ]],
                ],
            ])
            ->throw();

        return (string) $response->json('messages.0.id');
    }
}
