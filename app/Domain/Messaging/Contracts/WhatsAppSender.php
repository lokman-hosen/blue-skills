<?php

namespace App\Domain\Messaging\Contracts;

interface WhatsAppSender
{
    /**
     * Sends the approved update template (D-067) and returns the provider message id.
     *
     * @param  list<string>  $parameters  Body parameters in template order.
     */
    public function sendTemplate(string $phone, string $template, string $locale, array $parameters): string;
}
