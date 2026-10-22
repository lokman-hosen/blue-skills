<?php

namespace App\Domain\Billing\Support;

use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use Illuminate\Validation\ValidationException;

/**
 * Plan limits: posting credits, seats and talent search (spec §11). Tracked always, enforced
 * only when `blueskills.billing.enforce` is on, which waits for approved pricing (D-095).
 */
final class BillingLimits
{
    public static function enforced(): bool
    {
        return (bool) config('blueskills.billing.enforce');
    }

    /**
     * A job's first submission needs a posting credit; resubmitting a live job doesn't.
     */
    public static function ensureCanSubmit(JobPosting $job): void
    {
        if (self::enforced() && $job->published_at === null && $job->organization->creditBalance() < 1) {
            throw ValidationException::withMessages(['job' => __('billing.limits.no_credits')]);
        }
    }

    /**
     * Uses a credit when a job is published for the first time. The balance may dip below zero
     * if credits ran out while the job waited for review; the billing page shows it.
     */
    public static function chargePublication(JobPosting $job): void
    {
        if (self::enforced() && $job->published_at === null) {
            CreditLedgerEntry::query()->create([
                'organization_id' => $job->organization_id,
                'delta' => -1,
                'reason' => CreditReason::JobPublished,
                'job_id' => $job->id,
            ]);
        }
    }

    public static function ensureSeatAvailable(Organization $organization): void
    {
        $seats = $organization->activeSubscription?->plan->seats;

        if (! self::enforced() || $seats === null) {
            return;
        }

        $used = $organization->members()->count()
            + $organization->invitations()->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count();

        if ($used >= $seats) {
            throw ValidationException::withMessages(['email' => __('billing.limits.no_seats', ['seats' => $seats])]);
        }
    }

    public static function allowsTalentSearch(Organization $organization): bool
    {
        return ! self::enforced() || ($organization->activeSubscription?->plan->talent_search ?? false);
    }
}
