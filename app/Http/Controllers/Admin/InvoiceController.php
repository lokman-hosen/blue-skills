<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Billing\Actions\IssueInvoice;
use App\Domain\Billing\Actions\MarkInvoicePaid;
use App\Domain\Billing\Actions\SaveInvoiceLine;
use App\Domain\Billing\Actions\VoidInvoice;
use App\Domain\Billing\Data\InvoiceData;
use App\Domain\Billing\Data\InvoiceListItemData;
use App\Domain\Billing\Enums\PaymentMethod;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceLine;
use App\Domain\Billing\States\Invoice\Issued;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceLineRequest;
use App\Http\Requests\Admin\MarkInvoicePaidRequest;
use App\Http\Requests\Admin\VoidInvoiceRequest;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invoices: drafts are edited line by line, issued (numbered, owners told), marked paid when the
 * transfer arrives (credits added) or voided (D-095).
 */
class InvoiceController extends Controller
{
    public const STATES = ['open', 'overdue', 'draft', 'paid', 'void', 'all'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);
        $state = in_array($request->string('state')->toString(), self::STATES, true) ? $request->string('state')->toString() : 'open';

        $invoices = Invoice::query()
            ->when($state === 'open', fn (Builder $query) => $query->whereState('status', Issued::class))
            ->when($state === 'overdue', fn (Builder $query) => $query->whereState('status', Issued::class)->where('due_on', '<', now()->toDateString()))
            ->when(in_array($state, ['draft', 'paid', 'void'], true), fn (Builder $query) => $query->where('status', $state))
            ->with('organization')
            ->latest()
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/billing/invoices', [
            'invoices' => $invoices->through(fn (Invoice $invoice) => InvoiceListItemData::fromInvoice($invoice)),
            'state' => $state,
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        Gate::authorize('update', $invoice);

        return Inertia::render('admin/billing/invoice', [
            'invoice' => InvoiceData::fromInvoice($invoice->load(['organization', 'lines'])),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function addLine(InvoiceLineRequest $request, Invoice $invoice, SaveInvoiceLine $save): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $save->add(
            $invoice,
            $request->string('description')->trim()->toString(),
            $request->integer('quantity'),
            Money::parseMinor($request->string('unit_amount')->toString(), $invoice->currency),
            $request->integer('credits'),
        );

        return to_route('admin.billing.invoices.show', $invoice);
    }

    public function removeLine(Invoice $invoice, InvoiceLine $line, SaveInvoiceLine $save): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $save->remove($invoice, $line);

        return to_route('admin.billing.invoices.show', $invoice);
    }

    public function issue(Request $request, Invoice $invoice, IssueInvoice $issue): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $issue->handle($invoice, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.issued', ['number' => (string) $invoice->fresh()?->number])]);

        return to_route('admin.billing.invoices.show', $invoice);
    }

    public function pay(MarkInvoicePaidRequest $request, Invoice $invoice, MarkInvoicePaid $pay): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $pay->handle(
            $invoice,
            PaymentMethod::from($request->string('payment_method')->toString()),
            $request->string('payment_reference')->trim()->toString(),
            Carbon::parse($request->string('paid_on')->toString()),
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.paid')]);

        return to_route('admin.billing.invoices.show', $invoice);
    }

    public function void(VoidInvoiceRequest $request, Invoice $invoice, VoidInvoice $void): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $void->handle($invoice, $request->string('note')->trim()->toString(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.voided')]);

        return to_route('admin.billing.invoices.show', $invoice);
    }
}
