<?php

namespace App\Domain\Organization\Data;

use App\Domain\Worker\Support\MediaUrls;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EvidenceDocumentData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $sizeKb,
        public ?string $uploadedAt,
        /** Signed, short-lived link; only set for reviewers. */
        public ?string $url = null,
    ) {}

    public static function fromMedia(Media $media, bool $withUrl = false): self
    {
        return new self(
            id: (string) $media->uuid,
            name: $media->name,
            sizeKb: (int) ceil($media->size / 1024),
            uploadedAt: $media->created_at?->toIso8601String(),
            url: $withUrl ? MediaUrls::signed($media) : null,
        );
    }
}
