<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;

/**
 * A draft invoice for an organization, optionally starting with a line for its plan (and the
 * plan's posting credits, added when paid). VAT comes from the billing settings.
 */
class CreateInvoice
{
    public function handle(Organization $organization, ?OrganizationSubscription $subscription, User $actor): Invoice
    {
        $plan = $subscription?->plan;

        $invoice = $organization->invoices()->create([
            'organization_subscription_id' => $subscription?->id,
            'currency' => $plan !== null ? $plan->price_currency : config('blueskills.billing.currency'),
            'tax_rate_bp' => (int) config('blueskills.billing.vat_rate_bp'),
            'provider' => config('blueskills.billing.provider'),
            'created_by' => $actor->id,
        ]);

        if ($plan !== null) {
            $invoice->lines()->create([
                'description' => $plan->name,
                'quantity' => 1,
                'unit_amount_minor' => $plan->price_amount_minor,
                'amount_minor' => $plan->price_amount_minor,
                'plan_id' => $plan->id,
                'credits' => $plan->posting_credits,
            ]);
            $invoice->recalculate();
        }

        return $invoice;
    }
}
