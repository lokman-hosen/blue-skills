<?php

namespace App\Domain\Organization\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\OrganizationType;
use Database\Factories\PilotInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A code staff give a selected partner so it can register an organization during the pilot
 * (D-108). It can be limited to one organization type, a number of uses and a date.
 *
 * @property OrganizationType|null $organization_type
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['code', 'partner_name', 'organization_type', 'max_uses', 'used_count', 'expires_at', 'revoked_at', 'created_by'])]
#[UseFactory(PilotInviteFactory::class)]
class PilotInvite extends Model
{
    /** @use HasFactory<PilotInviteFactory> */
    use HasFactory, HasUlids;

    /** No 0/O, 1/I/L: codes are read out on the phone and typed from paper. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected function casts(): array
    {
        return [
            'organization_type' => OrganizationType::class,
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function newCode(): string
    {
        do {
            $code = 'PILOT-'.collect(range(1, 6))->map(fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])->implode('');
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public static function normalize(string $code): string
    {
        return Str::upper(trim($code));
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Organization, $this> */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /** active | used_up | expired | revoked */
    public function state(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->expires_at !== null && $this->expires_at->isPast() => 'expired',
            $this->used_count >= $this->max_uses => 'used_up',
            default => 'active',
        };
    }

    public function admits(OrganizationType $type): bool
    {
        return $this->state() === 'active' && ($this->organization_type === null || $this->organization_type === $type);
    }
}
