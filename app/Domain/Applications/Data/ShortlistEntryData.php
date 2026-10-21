<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\ShortlistEntry;
use Spatie\LaravelData\Data;

class ShortlistEntryData extends Data
{
    public function __construct(
        public string $id,
        public ?string $note,
        public string $addedAt,
        public ?string $addedBy,
        /** Null when the worker is no longer visible to the organization. */
        public ?TalentCardData $worker,
    ) {}

    public static function fromEntry(ShortlistEntry $entry, bool $visible): self
    {
        return new self(
            id: $entry->id,
            note: $entry->note,
            addedAt: $entry->created_at?->toIso8601String() ?? '',
            addedBy: $entry->adder?->name,
            worker: $visible ? TalentCardData::fromProfile($entry->workerProfile) : null,
        );
    }
}
