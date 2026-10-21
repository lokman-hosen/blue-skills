<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\InterviewMode;
use App\Domain\Applications\Enums\InterviewOutcome;
use App\Domain\Applications\Models\Interview;
use Spatie\LaravelData\Data;

class InterviewData extends Data
{
    public function __construct(
        public string $id,
        public string $scheduledAt,
        public int $durationMinutes,
        public InterviewMode $mode,
        public ?string $location,
        public ?string $instructions,
        public ?InterviewOutcome $outcome,
        public bool $upcoming,
    ) {}

    public static function fromInterview(Interview $interview): self
    {
        return new self(
            id: $interview->id,
            scheduledAt: $interview->scheduled_at->toIso8601String(),
            durationMinutes: $interview->duration_minutes,
            mode: $interview->mode,
            location: $interview->location,
            instructions: $interview->instructions,
            outcome: $interview->outcome,
            upcoming: $interview->isUpcoming(),
        );
    }
}
