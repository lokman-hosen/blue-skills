<?php

namespace App\Domain\Verification\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Sensitive file owned by a user. Stored on the private disk and served only through
 * signed, short-lived URLs after a policy and consent check.
 */
#[Fillable(['owner_user_id', 'type', 'title', 'expires_at'])]
#[UseFactory(DocumentFactory::class)]
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUlids, InteractsWithMedia, SoftDeletes;

    public const FILE_COLLECTION = 'file';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'expires_at' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILE_COLLECTION)
            ->singleFile()
            ->useDisk(config('blueskills.private_disk'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return MorphMany<EvidenceItem, $this>
     */
    public function evidenceItems(): MorphMany
    {
        return $this->morphMany(EvidenceItem::class, 'documentable');
    }
}
