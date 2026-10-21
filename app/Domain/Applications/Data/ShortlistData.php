<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\Shortlist;
use Spatie\LaravelData\Data;

class ShortlistData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $jobCode,
        public ?string $jobTitle,
        public int $entries,
        public ?string $createdBy,
        public string $createdAt,
    ) {}

    public static function fromShortlist(Shortlist $shortlist): self
    {
        return new self(
            id: $shortlist->id,
            name: $shortlist->name,
            jobCode: $shortlist->job?->public_code,
            jobTitle: $shortlist->job?->title,
            entries: (int) ($shortlist->getAttribute('entries_count') ?? $shortlist->entries()->count()),
            createdBy: $shortlist->creator?->name,
            createdAt: $shortlist->created_at?->toIso8601String() ?? '',
        );
    }
}
