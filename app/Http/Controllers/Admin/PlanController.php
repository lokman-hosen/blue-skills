<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Billing\Actions\SavePlan;
use App\Domain\Billing\Data\PlanData;
use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePlanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plans organizations can be put on (spec §11). Pricing needs approval before launch (D-095).
 */
class PlanController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Plan::class);

        return Inertia::render('admin/billing/plans', [
            'plans' => Plan::query()
                ->withCount(['subscriptions as active_subscriptions_count' => fn ($query) => $query->where('status', 'active')])
                ->orderBy('organization_type')->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (Plan $plan) => PlanData::fromPlan($plan))->values()->all(),
            'periods' => BillingPeriod::cases(),
            'currency' => config('blueskills.billing.currency'),
        ]);
    }

    public function store(SavePlanRequest $request, SavePlan $save): RedirectResponse
    {
        Gate::authorize('create', Plan::class);

        $save->handle(null, $request->plan());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.plan_saved')]);

        return to_route('admin.billing.plans.index');
    }

    public function update(SavePlanRequest $request, Plan $plan, SavePlan $save): RedirectResponse
    {
        Gate::authorize('update', $plan);

        $save->handle($plan, $request->plan());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.done.plan_saved')]);

        return to_route('admin.billing.plans.index');
    }
}
