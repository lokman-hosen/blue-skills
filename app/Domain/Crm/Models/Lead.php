<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A person BLUESKILLS follows up with: a worker who signed up, or someone entered by hand at a
 * job fair or call centre (spec §9). The conversion stage follows the worker's own progress;
 * the follow-up status is the CRM agent's (D-095).
 *
 * @property FollowUpStatus $follow_up_status
 * @property ConversionStage $conversion_stage
 * @property LeadSource $source
 * @property Carbon|null $next_follow_up_at
 * @property Carbon|null $last_contacted_at
 * @property Carbon|null $stage_reached_at
 */
#[Fillable([
    'user_id', 'name', 'phone', 'source', 'campaign_id', 'partner_organization_id', 'country_code', 'assigned_to',
    'follow_up_status', 'next_follow_up_at', 'last_contacted_at', 'conversion_stage', 'stage_reached_at', 'created_by',
])]
#[UseFactory(LeadFactory::class)]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, HasUlids, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'follow_up_status' => FollowUpStatus::class,
            'conversion_stage' => ConversionStage::class,
            'next_follow_up_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'stage_reached_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('crm')
            ->logOnly(['follow_up_status', 'assigned_to', 'next_follow_up_at', 'conversion_stage'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'partner_organization_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<LeadContactAttempt, $this>
     */
    public function contactAttempts(): HasMany
    {
        return $this->hasMany(LeadContactAttempt::class)->latest('attempted_at')->latest('id');
    }

    public function displayName(): string
    {
        return $this->user !== null ? $this->user->name : (string) $this->name;
    }

    public function phoneNumber(): ?string
    {
        return $this->user !== null ? $this->user->phone : $this->phone;
    }

    public function mayBeContacted(): bool
    {
        return $this->follow_up_status !== FollowUpStatus::DoNotContact;
    }
}
