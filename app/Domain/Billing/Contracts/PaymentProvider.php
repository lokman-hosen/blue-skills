<?php

namespace App\Domain\Billing\Contracts;

use App\Domain\Billing\Models\Invoice;

/**
 * Where an organization pays an invoice. BLUESKILLS stays integration-ready without hardwiring a
 * vendor (spec §13, D-095): `manual` (bank transfer, recorded by finance) until a provider is
 * chosen and pricing approved.
 */
interface PaymentProvider
{
    public function name(): string;

    /**
     * A link to pay online, or null when payment happens outside the platform.
     */
    public function paymentUrl(Invoice $invoice): ?string;
}
