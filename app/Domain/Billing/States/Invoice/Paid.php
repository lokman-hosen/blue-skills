<?php

namespace App\Domain\Billing\States\Invoice;

class Paid extends InvoiceState
{
    /** @var string */
    public static $name = 'paid';

    public function label(): string
    {
        return 'Paid';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
