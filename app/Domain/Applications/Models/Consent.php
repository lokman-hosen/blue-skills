<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Factories\ConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Explicit, per-purpose, revocable worker consent (spec §10). Revoking sets revoked_at;
 * rows are never deleted so the consent history survives.
 *
 * @property Carbon $granted_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['worker_profile_id', 'purpose', 'subject_type', 'subject_id', 'scope', 'granted_at', 'revoked_at'])]
#[UseFactory(ConsentFactory::class)]
class Consent extends Model
{
    /** @use HasFactory<ConsentFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => ConsentPurpose::class,
            'scope' => 'array',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('revoked_at');
    }
}
