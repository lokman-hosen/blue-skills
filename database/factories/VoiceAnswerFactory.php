<?php

namespace Database\Factories;

use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoiceAnswer>
 */
class VoiceAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_profile_id' => WorkerProfile::factory(),
            'question_key' => VoiceQuestion::Occupation,
            'locale' => 'en',
            'transcript' => fake()->sentence(),
            'extracted' => null,
            'status' => VoiceAnswerStatus::Transcribed,
        ];
    }
}
