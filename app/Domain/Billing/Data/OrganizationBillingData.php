<?php

namespace App\Domain\Billing\Data;

use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Support\BillingLimits;
use App\Domain\Organization\Models\Organization;
use Spatie\LaravelData\Data;

/**
 * An organization's plan, posting credits, seats and invoices (spec §11). `staffView` adds draft
 * invoices, which organizations never see (D-095).
 */
class OrganizationBillingData extends Data
{
    /**
     * @param  list<array{id: string, delta: int, reason: string, note: ?string, job: ?string, invoice: ?string, at: string}>  $ledger
     * @param  list<InvoiceListItemData>  $invoices
     */
    public function __construct(
        public string $organizationId,
        public string $organization,
        public ?PlanData $plan,
        public ?string $planSince,
        public int $credits,
        public int $seatsUsed,
        public ?int $seats,
        public bool $enforced,
        public array $ledger,
        public array $invoices,
    ) {}

    public static function for(Organization $organization, bool $staffView = false): self
    {
        $subscription = $organization->activeSubscription()->with('plan')->first();

        return new self(
            organizationId: $organization->id,
            organization: (string) $organization->display_name,
            plan: $subscription?->plan !== null ? PlanData::fromPlan($subscription->plan) : null,
            planSince: $subscription?->starts_on->toDateString(),
            credits: $organization->creditBalance(),
            seatsUsed: $organization->members()->count(),
            seats: $subscription?->plan?->seats,
            enforced: BillingLimits::enforced(),
            ledger: $organization->creditEntries()->with(['job', 'invoice'])->latest('created_at')->latest('id')->limit(20)->get()
                ->map(fn (CreditLedgerEntry $entry) => [
                    'id' => $entry->id,
                    'delta' => $entry->delta,
                    'reason' => $entry->reason->value,
                    'note' => $entry->note,
                    'job' => $entry->job?->title,
                    'invoice' => $entry->invoice?->number,
                    'at' => $entry->created_at->toIso8601String(),
                ])->values()->all(),
            invoices: $organization->invoices()
                ->when(! $staffView, fn ($query) => $query->whereNotState('status', 'draft'))
                ->with('organization')->latest()->limit(50)->get()
                ->map(fn (Invoice $invoice) => InvoiceListItemData::fromInvoice($invoice))->values()->all(),
        );
    }
}
