<?php

namespace App\Domain\Institute\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Factories\TalentPoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * An institute's group of graduates who accepted. Published pools can be browsed by verified
 * employers and agencies, who see only members visible to employers. Membership never moves
 * ownership of a profile (spec §8, D-082).
 *
 * @property Carbon|null $published_at
 */
#[Fillable(['organization_id', 'name', 'description', 'occupation_id', 'published_at', 'created_by'])]
#[UseFactory(TalentPoolFactory::class)]
class TalentPool extends Model
{
    /** @use HasFactory<TalentPoolFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<WorkerProfile, $this, TalentPoolMember>
     */
    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(WorkerProfile::class, 'talent_pool_members')
            ->using(TalentPoolMember::class)
            ->withPivot(['id', 'added_by'])
            ->withTimestamps();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
