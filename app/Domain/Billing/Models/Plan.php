<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Organization\Enums\OrganizationType;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * What an organization pays BLUESKILLS for: posting credits, seats, talent search and
 * recruitment projects (spec §11). Platform billing only; worker job costs are a separate ledger
 * (product rule 13).
 *
 * @property int $price_amount_minor
 * @property int $posting_credits
 * @property int|null $seats
 * @property bool $talent_search
 * @property bool $recruitment_projects
 * @property bool $is_active
 */
#[Fillable([
    'code', 'name', 'description', 'organization_type', 'price_amount_minor', 'price_currency', 'billing_period',
    'posting_credits', 'seats', 'talent_search', 'recruitment_projects', 'is_active', 'sort_order',
])]
#[UseFactory(PlanFactory::class)]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, HasUlids, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'organization_type' => OrganizationType::class,
            'billing_period' => BillingPeriod::class,
            'price_amount_minor' => 'integer',
            'posting_credits' => 'integer',
            'seats' => 'integer',
            'talent_search' => 'boolean',
            'recruitment_projects' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('billing')
            ->logOnly(['name', 'price_amount_minor', 'price_currency', 'billing_period', 'posting_credits', 'seats',
                'talent_search', 'recruitment_projects', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return HasMany<OrganizationSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }
}
