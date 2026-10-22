<?php

namespace App\Domain\Billing\Data;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\States\Invoice\Issued;
use App\Support\Data\MoneyData;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class InvoiceListItemData extends Data
{
    public function __construct(
        public string $id,
        public ?string $number,
        public string $organization,
        /** Translated via `status.invoice_{value}`. */
        public StatusData $status,
        public MoneyData $total,
        public ?string $issuedAt,
        public ?string $dueOn,
        public bool $overdue,
        public ?string $paidAt,
    ) {}

    public static function fromInvoice(Invoice $invoice): self
    {
        return new self(
            id: $invoice->id,
            number: $invoice->number,
            organization: (string) $invoice->organization->display_name,
            status: self::status($invoice),
            total: new MoneyData($invoice->total_minor, $invoice->currency),
            issuedAt: $invoice->issued_at?->toIso8601String(),
            dueOn: $invoice->due_on?->toDateString(),
            overdue: $invoice->status->equals(Issued::class) && $invoice->due_on !== null && $invoice->due_on->endOfDay()->isPast(),
            paidAt: $invoice->paid_at?->toIso8601String(),
        );
    }

    public static function status(Invoice $invoice): StatusData
    {
        /** @var string $value */
        $value = $invoice->status->getValue();

        return new StatusData('invoice_'.$value, $invoice->status->label(), $invoice->status->tone());
    }
}
