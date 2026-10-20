<?php

namespace App\Domain\Organization\Models;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Policies\OrganizationInvitationPolicy;
use Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Only a SHA-256 hash of the invitation token is stored.
 *
 * @property OrganizationRole $role
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['organization_id', 'email', 'phone', 'role', 'token_hash', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
#[UseFactory(OrganizationInvitationFactory::class)]
#[UsePolicy(OrganizationInvitationPolicy::class)]
class OrganizationInvitation extends Model
{
    /** @use HasFactory<OrganizationInvitationFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Invitations still waiting for the given address.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pendingFor(Builder $query, string $email): void
    {
        $query->whereRaw('lower(email) = ?', [mb_strtolower($email)])
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
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
