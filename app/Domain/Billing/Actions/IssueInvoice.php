<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\States\Invoice\Issued;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issues a draft: it gets its number and due date and can no longer change. The organization's
 * billing managers are told (in-app and email).
 */
class IssueInvoice
{
    public function handle(Invoice $invoice, User $actor): void
    {
        if (! $invoice->isDraft()) {
            throw ValidationException::withMessages(['invoice' => __('billing.errors.not_draft')]);
        }

        if (! $invoice->lines()->exists()) {
            throw ValidationException::withMessages(['invoice' => __('billing.errors.no_lines')]);
        }

        DB::transaction(function () use ($invoice): void {
            $invoice->status->transitionTo(Issued::class);
            $invoice->update([
                'number' => Invoice::newNumber(),
                'issued_at' => now(),
                'due_on' => now()->addDays((int) config('blueskills.billing.payment_days'))->toDateString(),
            ]);
        });

        $params = [
            'number' => (string) $invoice->number,
            'amount' => number_format(Money::toWhole($invoice->total_minor, $invoice->currency)).' '.$invoice->currency,
        ];

        foreach (Notify::members($invoice->organization, Permission::BillingManage) as $member) {
            Notify::user($member, NotificationEvent::InvoiceIssued, $params, route('org.billing.invoices.show', $invoice, false));
        }
    }
}
