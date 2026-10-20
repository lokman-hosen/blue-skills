<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Enums\VoiceQuestion;
use Spatie\LaravelData\Data;

class VoiceProgressData extends Data
{
    public function __construct(
        public VoiceQuestion $question,
        public bool $confirmed,
    ) {}
}
