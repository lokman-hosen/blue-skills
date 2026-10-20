<?php

namespace App\Domain\Identity\Contracts;

/**
 * Delivers a sign-in code to a phone. Implementations must not log the code in production.
 */
interface OtpChannel
{
    public function name(): string;

    public function send(string $phone, string $code, string $locale): void;
}
