<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Billing\Actions\AdjustCredits;
use App\Domain\Billing\Actions\AssignPlan;
use App\Domain\Billing\Actions\CreateInvoice;
use App\Domain\Billing\Data\OrganizationBillingData;
use App\Domain\Billing\Data\PlanData;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\States\Invoice\Issued;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustCreditsRequest;
use App\Http\Requests\Admin\AssignPlanRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform billing for Finance Officers (spec §11, D-095): each organization's plan, posting
 * credits and invoices. A ledger separate from worker job costs (product rule 13).
 */
class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);
        $q = mb_substr($request->string('q')->trim()->toString(), 0, 80) ?: null;

        $organizations = Organization::query()
            ->whereIn('type', [OrganizationType::Employer, OrganizationType::Agency, OrganizationType::Institute])
            ->when($q, fn (Builder $query, string $q) => $query->where('display_name', 'ilike', "%{$q}%"))
            ->with('activeSubscription.plan')
            ->withSum('creditEntries as credits', 'delta')
            ->withSum(['invoices as unpaid_minor' => fn (Builder $query) => $query->whereState('status', Issued::class)], 'total_minor')
            ->orderBy('display_name')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/billing/index', [
            'organizations' => $organizations->through(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => (string) $organization->display_name,
                'type' => $organization->type->value,
                'plan' => $organization->activeSubscription?->plan->name,
                'credits' => (int) $organization->getAttribute('credits'),
                'unpaid' => ['amountMinor' => (int) $organization->getAttribute('unpaid_minor'), 'currency' => (string) config('blueskills.billing.currency')],
            ]),
            'q' => $q,
        ]);
    }

    public function show(Organization $organization): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        return Inertia::render('admin/billing/organization', [
            'billing' => OrganizationBillingData::for($organization, staffView: true),
            'organizationType' => $organization->type->value,
            'plans' => Plan::query()->where('is_active', true)->where('organization_type', $organization->type)->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (Plan $plan) => PlanData::fromPlan($plan))->values()->all(),
        ]);
    }

    public function assignPlan(AssignPlanRequest $request, Organization $organization, AssignPlan $assign, CreateInvoice $createInvoice): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $plan = Plan::query()->findOrFail($request->string('plan_id')->toString());
        $subscription = $assign->handle($organization, $plan, Carbon::parse($request->string('starts_on')->toString()), $request->user());

        if ($request->boolean('create_invoice')) {
            $invoice = $createInvoice->handle($organization, $subscription, $request->user());
            Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.plan_with_invoice')]);

            return to_route('admin.billing.invoices.show', $invoice);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.plan')]);

        return to_route('admin.billing.organizations.show', $organization);
    }

    public function adjustCredits(AdjustCreditsRequest $request, Organization $organization, AdjustCredits $adjust): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $adjust->handle($organization, $request->integer('delta'), $request->string('note')->trim()->toString(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.credits')]);

        return to_route('admin.billing.organizations.show', $organization);
    }

    public function createInvoice(Request $request, Organization $organization, CreateInvoice $create): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $invoice = $create->handle($organization, null, $request->user());

        return to_route('admin.billing.invoices.show', $invoice);
    }
}
