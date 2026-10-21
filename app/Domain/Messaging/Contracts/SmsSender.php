<?php

namespace App\Domain\Messaging\Contracts;

interface SmsSender
{
    /**
     * Sends a text message and returns the provider message id.
     */
    public function send(string $phone, string $text): string;
}
