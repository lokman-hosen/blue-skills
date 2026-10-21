<?php

namespace App\Domain\Messaging\Channels;

use App\Domain\Messaging\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use Propaganistas\LaravelPhone\PhoneNumber;
use RuntimeException;

/**
 * Twilio Programmable Messaging over its REST API (D-067). The sender is chosen per destination
 * country, because alphanumeric sender IDs and numbers are regulated country by country.
 */
class TwilioSmsSender implements SmsSender
{
    public function send(string $phone, string $text): string
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (blank($sid) || blank($token)) {
            throw new RuntimeException('Twilio is not configured: set TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.');
        }

        $response = Http::withBasicAuth((string) $sid, (string) $token)
            ->asForm()
            ->acceptJson()
            ->timeout(10)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $phone,
                'Body' => $text,
                ...$this->sender($phone),
            ])
            ->throw();

        return (string) $response->json('sid');
    }

    /**
     * @return array<string, string>
     */
    private function sender(string $phone): array
    {
        /** @var array<string, string> $senders */
        $senders = config('services.twilio.senders', []);
        $country = (new PhoneNumber($phone))->getCountry();

        if ($country !== null && filled($senders[$country] ?? null)) {
            return ['From' => $senders[$country]];
        }

        if (filled(config('services.twilio.messaging_service_sid'))) {
            return ['MessagingServiceSid' => (string) config('services.twilio.messaging_service_sid')];
        }

        if (filled(config('services.twilio.from'))) {
            return ['From' => (string) config('services.twilio.from')];
        }

        throw new RuntimeException('Twilio has no sender for this number: set TWILIO_SENDERS, TWILIO_FROM or TWILIO_MESSAGING_SERVICE_SID.');
    }
}
