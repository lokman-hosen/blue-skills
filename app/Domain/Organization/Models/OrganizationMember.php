<?php

namespace App\Domain\Organization\Models;

use App\Domain\Identity\Models\User;
use Database\Factories\OrganizationMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Membership of a user in an organization. Roles live in spatie-permission with
 * team = organization_id.
 */
#[Fillable(['organization_id', 'user_id', 'title', 'invited_by', 'joined_at'])]
#[UseFactory(OrganizationMemberFactory::class)]
class OrganizationMember extends Pivot
{
    /** @use HasFactory<OrganizationMemberFactory> */
    use HasFactory, HasUlids;

    protected $table = 'organization_members';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
