<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\States\Invoice\Voided;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Cancels a draft or an unpaid invoice with a note. Its number is kept so the sequence has no
 * gaps; a paid invoice can't be voided.
 */
class VoidInvoice
{
    public function handle(Invoice $invoice, string $note, User $actor): void
    {
        if (! $invoice->status->canTransitionTo(Voided::class)) {
            throw ValidationException::withMessages(['invoice' => __('billing.errors.cannot_void')]);
        }

        $invoice->status->transitionTo(Voided::class);
        $invoice->update(['voided_at' => now(), 'notes' => trim(($invoice->notes ? $invoice->notes."\n" : '').$note)]);

        activity('billing')->performedOn($invoice)->causedBy($actor)->withProperties(['note' => $note])->log('invoice.voided');
    }
}
