<?php

namespace App\Support\Data;

use Spatie\LaravelData\Data;

/**
 * Money in the currency's minor unit (JPY has none). Formatted on the client with Intl.
 */
class MoneyData extends Data
{
    public function __construct(
        public int $amountMinor,
        public string $currency,
    ) {}
}
