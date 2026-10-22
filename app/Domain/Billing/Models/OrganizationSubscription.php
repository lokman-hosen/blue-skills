<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\States\Subscription\Active;
use App\Domain\Billing\States\Subscription\SubscriptionState;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\OrganizationSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * An organization's plan, assigned by a Finance Officer (D-095).
 *
 * @property SubscriptionState $status
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon|null $cancelled_at
 */
#[Fillable(['organization_id', 'plan_id', 'status', 'starts_on', 'ends_on', 'created_by', 'cancelled_at'])]
#[UseFactory(OrganizationSubscriptionFactory::class)]
class OrganizationSubscription extends Model
{
    /** @use HasFactory<OrganizationSubscriptionFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionState::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'plan_id', 'ends_on'];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status->equals(Active::class);
    }
}
