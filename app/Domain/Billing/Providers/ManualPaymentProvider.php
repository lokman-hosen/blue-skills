<?php

namespace App\Domain\Billing\Providers;

use App\Domain\Billing\Contracts\PaymentProvider;
use App\Domain\Billing\Models\Invoice;

/**
 * Bank transfer quoting the invoice number; a Finance Officer marks the invoice paid.
 */
class ManualPaymentProvider implements PaymentProvider
{
    public function name(): string
    {
        return 'manual';
    }

    public function paymentUrl(Invoice $invoice): ?string
    {
        return null;
    }
}
