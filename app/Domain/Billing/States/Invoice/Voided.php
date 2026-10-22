<?php

namespace App\Domain\Billing\States\Invoice;

class Voided extends InvoiceState
{
    /** @var string */
    public static $name = 'void';

    public function label(): string
    {
        return 'Void';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
