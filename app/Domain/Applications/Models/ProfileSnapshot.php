<?php

namespace App\Domain\Applications\Models;

use Database\Factories\ProfileSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable copy of the worker profile taken when they applied.
 */
#[Fillable(['application_id', 'data', 'taken_at'])]
#[UseFactory(ProfileSnapshotFactory::class)]
class ProfileSnapshot extends Model
{
    /** @use HasFactory<ProfileSnapshotFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Profile snapshots are immutable.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'taken_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
