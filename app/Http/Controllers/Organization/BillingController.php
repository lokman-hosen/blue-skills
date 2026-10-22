<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Billing\Data\InvoiceData;
use App\Domain\Billing\Data\OrganizationBillingData;
use App\Domain\Billing\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The workspace billing page (spec §14 "Billing"): plan, posting credits, seats and invoices.
 * Owners and Agency Owners (`billing.manage`).
 */
class BillingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewBilling', $organization);

        return Inertia::render('org/billing/index', [
            'billing' => OrganizationBillingData::for($organization),
        ]);
    }

    public function invoice(Request $request, Invoice $invoice): Response
    {
        $organization = $this->organization($request);
        abort_unless($invoice->organization_id === $organization->id, 404);
        Gate::authorize('view', $invoice);

        return Inertia::render('org/billing/invoice', [
            'invoice' => InvoiceData::fromInvoice($invoice->load(['organization', 'lines'])),
        ]);
    }
}
