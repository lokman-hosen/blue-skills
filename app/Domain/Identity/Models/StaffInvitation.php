<?php

namespace App\Domain\Identity\Models;

use BackedEnum;
use Database\Factories\StaffInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $email
 * @property string $role Built-in or created staff role name (D-113)
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['email', 'role', 'token_hash', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
#[UseFactory(StaffInvitationFactory::class)]
class StaffInvitation extends Model
{
    /** @use HasFactory<StaffInvitationFactory> */
    use HasFactory, HasUlids;

    public const VALID_DAYS = 3;

    /**
     * @return array<string, string>
     */
    /**
     * Always the role's name, built-in or created by the Superuser (D-113); a built-in role enum
     * is accepted too.
     *
     * @return Attribute<string, BackedEnum|string>
     */
    protected function role(): Attribute
    {
        return Attribute::make(set: fn (BackedEnum|string $role) => $role instanceof BackedEnum ? (string) $role->value : $role);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findPendingByToken(string $token): ?self
    {
        $invitation = self::query()->where('token_hash', self::hashToken($token))->first();

        return $invitation?->isPending() ? $invitation : null;
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
