<?php

namespace App\Domain\Trust\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Evidence on a trust case: what an officer found, with screenshots, call logs or payment
 * records on the private disk. Never edited.
 */
#[Fillable(['trust_case_id', 'author_id', 'body'])]
class TrustCaseNote extends Model implements HasMedia
{
    use HasUlids, InteractsWithMedia;

    public const FILES = 'files';

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Trust case notes are immutable.');
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILES)->useDisk(config('blueskills.private_disk'));
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
