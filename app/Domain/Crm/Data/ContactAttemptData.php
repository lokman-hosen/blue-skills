<?php

namespace App\Domain\Crm\Data;

use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Crm\Models\LeadContactAttempt;
use Spatie\LaravelData\Data;

class ContactAttemptData extends Data
{
    public function __construct(
        public string $id,
        public ContactChannel $channel,
        public ContactOutcome $outcome,
        public ?string $note,
        public ?string $staff,
        public string $attemptedAt,
    ) {}

    public static function fromAttempt(LeadContactAttempt $attempt): self
    {
        return new self(
            id: $attempt->id,
            channel: $attempt->channel,
            outcome: $attempt->outcome,
            note: $attempt->note,
            staff: $attempt->staff?->name,
            attemptedAt: $attempt->attempted_at->toIso8601String(),
        );
    }
}
