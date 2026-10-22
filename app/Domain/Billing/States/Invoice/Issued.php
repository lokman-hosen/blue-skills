<?php

namespace App\Domain\Billing\States\Invoice;

class Issued extends InvoiceState
{
    /** @var string */
    public static $name = 'issued';

    public function label(): string
    {
        return 'Issued';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
