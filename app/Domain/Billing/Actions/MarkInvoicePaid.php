<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Enums\PaymentMethod;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceLine;
use App\Domain\Billing\States\Invoice\Issued;
use App\Domain\Billing\States\Invoice\Paid;
use App\Domain\Identity\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Finance records the payment (method and bank reference). The credits on the invoice's lines
 * are added to the organization's ledger, once.
 */
class MarkInvoicePaid
{
    public function handle(Invoice $invoice, PaymentMethod $method, string $reference, CarbonInterface $paidAt, User $actor): void
    {
        if (! $invoice->status->equals(Issued::class)) {
            throw ValidationException::withMessages(['invoice' => __('billing.errors.not_issued')]);
        }

        DB::transaction(function () use ($invoice, $method, $reference, $paidAt, $actor): void {
            $invoice->status->transitionTo(Paid::class);
            $invoice->update(['payment_method' => $method, 'payment_reference' => $reference, 'paid_at' => $paidAt]);

            $invoice->lines->filter(fn (InvoiceLine $line) => $line->credits > 0)
                ->each(fn (InvoiceLine $line) => CreditLedgerEntry::query()->create([
                    'organization_id' => $invoice->organization_id,
                    'delta' => $line->credits * $line->quantity,
                    'reason' => $line->plan_id !== null ? CreditReason::PlanGrant : CreditReason::Purchase,
                    'note' => $line->description,
                    'invoice_id' => $invoice->id,
                    'actor_id' => $actor->id,
                ]));
        });
    }
}
