<?php

namespace App\Domain\Messaging\Data;

use App\Domain\Worker\Support\MediaUrls;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AttachmentData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $size,
        public ?string $url,
    ) {}

    public static function fromMedia(Media $media): self
    {
        return new self($media->uuid ?? (string) $media->id, $media->name, (int) $media->size, MediaUrls::signed($media));
    }
}
