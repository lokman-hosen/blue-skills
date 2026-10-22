<?php

namespace App\Domain\Billing\Data;

use App\Domain\Billing\Contracts\PaymentProvider;
use App\Domain\Billing\Enums\PaymentMethod;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceLine;
use App\Support\Data\MoneyData;
use Spatie\LaravelData\Data;

/**
 * An invoice as finance and the organization see it. Platform billing only; worker job costs
 * never appear here (product rule 13).
 */
class InvoiceData extends Data
{
    /**
     * @param  list<array{id: string, description: string, quantity: int, unitAmount: MoneyData, amount: MoneyData, credits: int}>  $lines
     */
    public function __construct(
        public InvoiceListItemData $summary,
        public string $organizationId,
        public ?string $organizationLegalName,
        public ?string $organizationCountryCode,
        public MoneyData $subtotal,
        public float $taxRatePercent,
        public MoneyData $tax,
        public array $lines,
        public ?PaymentMethod $paymentMethod,
        public ?string $paymentReference,
        public ?string $notes,
        public ?string $paymentUrl,
        public ?string $paymentInstructions,
    ) {}

    public static function fromInvoice(Invoice $invoice): self
    {
        $money = fn (int $minor) => new MoneyData($minor, $invoice->currency);

        return new self(
            summary: InvoiceListItemData::fromInvoice($invoice),
            organizationId: $invoice->organization_id,
            organizationLegalName: $invoice->organization->legal_name,
            organizationCountryCode: $invoice->organization->country_code,
            subtotal: $money($invoice->subtotal_minor),
            taxRatePercent: $invoice->tax_rate_bp / 100,
            tax: $money($invoice->tax_minor),
            lines: $invoice->lines->map(fn (InvoiceLine $line) => [
                'id' => $line->id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unitAmount' => $money($line->unit_amount_minor),
                'amount' => $money($line->amount_minor),
                'credits' => $line->credits,
            ])->values()->all(),
            paymentMethod: $invoice->payment_method,
            paymentReference: $invoice->payment_reference,
            notes: $invoice->notes,
            paymentUrl: $invoice->isDraft() ? null : app(PaymentProvider::class)->paymentUrl($invoice),
            paymentInstructions: config('blueskills.billing.payment_instructions'),
        );
    }
}
