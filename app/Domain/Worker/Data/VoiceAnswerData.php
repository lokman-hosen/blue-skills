<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\VoiceAnswer;
use Spatie\LaravelData\Data;

class VoiceAnswerData extends Data
{
    public function __construct(
        public string $id,
        public VoiceQuestion $question,
        public VoiceAnswerStatus $status,
        public bool $typed,
        public ?string $transcript,
        public ?VoiceFieldsData $fields,
        /** unavailable | no_speech | provider_error */
        public ?string $errorCode,
        public ?string $confirmedAt,
    ) {}

    public static function fromAnswer(VoiceAnswer $answer): self
    {
        return new self(
            id: $answer->id,
            question: $answer->question_key,
            status: $answer->status,
            typed: $answer->typed,
            transcript: $answer->transcript,
            fields: $answer->extracted === null ? null : VoiceFieldsData::fromStored($answer->extracted),
            errorCode: $answer->error_code,
            confirmedAt: $answer->confirmed_at?->toIso8601String(),
        );
    }
}
