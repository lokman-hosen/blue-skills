<?php

namespace App\Domain\Trust\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use Database\Factories\TrustActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * A suspension or restoration of an organization, job or worker, with its reason code. The
 * history is append-only: lifting a suspension adds a restore action (spec §10).
 */
#[Fillable(['trust_case_id', 'subject_type', 'subject_id', 'action', 'reason_code', 'note', 'actor_id'])]
#[UseFactory(TrustActionFactory::class)]
class TrustAction extends Model
{
    /** @use HasFactory<TrustActionFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Trust actions are immutable.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => TrustActionType::class,
            'reason_code' => TrustReason::class,
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<TrustCase, $this>
     */
    public function trustCase(): BelongsTo
    {
        return $this->belongsTo(TrustCase::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return HasMany<Appeal, $this>
     */
    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class);
    }
}
