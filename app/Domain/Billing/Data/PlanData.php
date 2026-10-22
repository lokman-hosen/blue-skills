<?php

namespace App\Domain\Billing\Data;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Organization\Enums\OrganizationType;
use App\Support\Data\MoneyData;
use Spatie\LaravelData\Data;

class PlanData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public ?string $description,
        public OrganizationType $organizationType,
        public MoneyData $price,
        public BillingPeriod $billingPeriod,
        public int $postingCredits,
        public ?int $seats,
        public bool $talentSearch,
        public bool $recruitmentProjects,
        public bool $isActive,
        public int $subscribers,
    ) {}

    public static function fromPlan(Plan $plan): self
    {
        return new self(
            id: $plan->id,
            code: $plan->code,
            name: $plan->name,
            description: $plan->description,
            organizationType: $plan->organization_type,
            price: new MoneyData($plan->price_amount_minor, $plan->price_currency),
            billingPeriod: $plan->billing_period,
            postingCredits: $plan->posting_credits,
            seats: $plan->seats,
            talentSearch: $plan->talent_search,
            recruitmentProjects: $plan->recruitment_projects,
            isActive: $plan->is_active,
            subscribers: (int) ($plan->getAttribute('active_subscriptions_count') ?? 0),
        );
    }
}
