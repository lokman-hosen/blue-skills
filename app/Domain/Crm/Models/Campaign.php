<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\CampaignChannel;
use App\Domain\Crm\Enums\CampaignLanding;
use App\Domain\Crm\States\Campaign\Active;
use App\Domain\Crm\States\Campaign\CampaignState;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\ModelStates\HasStates;

/**
 * A marketing campaign with its own link /c/{code} (spec §9). Visitors are counted per day;
 * people who sign up through it become its leads.
 *
 * @property CampaignState $status
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 */
#[Fillable([
    'code', 'name', 'channel', 'source_country_code', 'partner_organization_id', 'landing', 'status', 'starts_on',
    'ends_on', 'created_by',
])]
#[UseFactory(CampaignFactory::class)]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'landing' => CampaignLanding::class,
            'status' => CampaignState::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'name', 'ends_on'];
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CampaignVisit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(CampaignVisit::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isActive(): bool
    {
        return $this->status->equals(Active::class);
    }

    /**
     * A short code people can type: upper-case letters and digits from the name, made unique.
     */
    public static function newCode(string $name): string
    {
        $base = Str::of($name)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '-')->trim('-')->limit(20, '')->trim('-')->toString();
        $base = $base !== '' ? $base : 'CAMPAIGN';
        $code = $base;
        $n = 2;
        while (self::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$n++;
        }

        return $code;
    }
}
