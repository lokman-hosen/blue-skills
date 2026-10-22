<?php

namespace App\Domain\Billing\States\Invoice;

class Draft extends InvoiceState
{
    /** @var string */
    public static $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
