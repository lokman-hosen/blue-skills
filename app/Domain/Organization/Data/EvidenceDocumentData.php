<?php

namespace App\Domain\Organization\Data;

use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EvidenceDocumentData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $sizeKb,
        public ?string $uploadedAt,
    ) {}

    public static function fromMedia(Media $media): self
    {
        return new self(
            id: (string) $media->uuid,
            name: $media->name,
            sizeKb: (int) ceil($media->size / 1024),
            uploadedAt: $media->created_at?->toIso8601String(),
        );
    }
}
