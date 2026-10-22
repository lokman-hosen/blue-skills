<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Plan;
use Illuminate\Support\Str;

/**
 * Finance sets up what organizations can buy (spec §11). The code is set once from the name;
 * plans are switched off, never deleted, so old subscriptions and invoices keep their plan.
 */
class SavePlan
{
    /**
     * @param  array{name: string, description: ?string, organization_type: string, price_amount_minor: int, price_currency: string, billing_period: string, posting_credits: int, seats: ?int, talent_search: bool, recruitment_projects: bool, is_active: bool}  $attributes
     */
    public function handle(?Plan $plan, array $attributes): Plan
    {
        if ($plan !== null) {
            $plan->update($attributes);

            return $plan;
        }

        $base = Str::slug($attributes['organization_type'].' '.$attributes['name']);
        $code = $base;
        $n = 2;
        while (Plan::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$n++;
        }

        return Plan::query()->create([...$attributes, 'code' => $code]);
    }
}
