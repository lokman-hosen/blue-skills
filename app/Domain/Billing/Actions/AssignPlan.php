<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\States\Subscription\Ended;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Puts an organization on a plan from a start date, ending its current plan the day before.
 * Credits arrive when the plan's invoice is paid, not on assignment (D-095).
 */
class AssignPlan
{
    public function handle(Organization $organization, Plan $plan, Carbon $startsOn, User $actor): OrganizationSubscription
    {
        if (! $plan->is_active || $plan->organization_type !== $organization->type) {
            throw ValidationException::withMessages(['plan_id' => __('billing.errors.plan_not_for_organization')]);
        }

        return DB::transaction(function () use ($organization, $plan, $startsOn, $actor): OrganizationSubscription {
            $current = $organization->activeSubscription()->first();

            if ($current !== null) {
                $current->status->transitionTo(Ended::class);
                $current->update(['ends_on' => $startsOn->copy()->subDay()->max($current->starts_on)->toDateString()]);
            }

            $subscription = $organization->subscriptions()->create([
                'plan_id' => $plan->id,
                'starts_on' => $startsOn->toDateString(),
                'created_by' => $actor->id,
            ]);

            activity('billing')->performedOn($organization)->causedBy($actor)
                ->withProperties(['plan' => $plan->code, 'previous' => $current?->plan?->code])->log('plan.assigned');

            return $subscription;
        });
    }
}
