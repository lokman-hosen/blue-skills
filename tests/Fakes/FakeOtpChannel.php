<?php

namespace Tests\Fakes;

use App\Domain\Identity\Contracts\OtpChannel;

class FakeOtpChannel implements OtpChannel
{
    /** @var list<array{phone: string, code: string, locale: string}> */
    public array $sent = [];

    public function name(): string
    {
        return 'fake';
    }

    public function send(string $phone, string $code, string $locale): void
    {
        $this->sent[] = compact('phone', 'code', 'locale');
    }

    public function lastCode(): string
    {
        return $this->sent[array_key_last($this->sent)]['code'];
    }
}
