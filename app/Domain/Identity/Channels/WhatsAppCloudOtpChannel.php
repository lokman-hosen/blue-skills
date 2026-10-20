<?php

namespace App\Domain\Identity\Channels;

use App\Domain\Identity\Contracts\OtpChannel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends the code with an approved WhatsApp "authentication" template through the Meta Cloud API.
 * The template has one body parameter (the code) and a copy-code URL button.
 */
class WhatsAppCloudOtpChannel implements OtpChannel
{
    /**
     * Template language codes WhatsApp supports for our interface locales.
     */
    private const TEMPLATE_LANGUAGES = ['en' => 'en', 'bn' => 'bn', 'hi' => 'hi', 'ur' => 'ur', 'ta' => 'ta'];

    public function name(): string
    {
        return 'whatsapp';
    }

    public function send(string $phone, string $code, string $locale): void
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (blank($token) || blank($phoneNumberId)) {
            throw new RuntimeException('WhatsApp is not configured: set WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID.');
        }

        $version = config('services.whatsapp.api_version');

        Http::withToken((string) $token)
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 500, throw: false)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($phone, '+'),
                'type' => 'template',
                'template' => [
                    'name' => config('services.whatsapp.otp_template'),
                    'language' => ['code' => self::TEMPLATE_LANGUAGES[$locale] ?? 'en'],
                    'components' => [
                        ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => '0',
                            'parameters' => [['type' => 'text', 'text' => $code]],
                        ],
                    ],
                ],
            ])
            ->throw();
    }
}
