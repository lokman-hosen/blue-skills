<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceLine;
use Illuminate\Validation\ValidationException;

/**
 * Adds or removes a line while the invoice is a draft. Issued invoices never change.
 */
class SaveInvoiceLine
{
    public function add(Invoice $invoice, string $description, int $quantity, int $unitAmountMinor, int $credits): InvoiceLine
    {
        $this->ensureDraft($invoice);

        $line = $invoice->lines()->create([
            'description' => $description,
            'quantity' => $quantity,
            'unit_amount_minor' => $unitAmountMinor,
            'amount_minor' => $quantity * $unitAmountMinor,
            'credits' => $credits,
        ]);
        $invoice->recalculate();

        return $line;
    }

    public function remove(Invoice $invoice, InvoiceLine $line): void
    {
        $this->ensureDraft($invoice);
        abort_unless($line->invoice_id === $invoice->id, 404);

        $line->delete();
        $invoice->recalculate();
    }

    private function ensureDraft(Invoice $invoice): void
    {
        if (! $invoice->isDraft()) {
            throw ValidationException::withMessages(['invoice' => __('billing.errors.not_draft')]);
        }
    }
}
