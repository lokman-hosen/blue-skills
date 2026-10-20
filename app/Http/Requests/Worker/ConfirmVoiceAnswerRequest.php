<?php

namespace App\Http\Requests\Worker;

use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Support\VoiceAnswerFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmVoiceAnswerRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Absent when the worker fills the fields in directly without speaking or typing.
            'answer_id' => ['nullable', 'string', Rule::exists('voice_answers', 'id')],
            ...VoiceAnswerFields::rules($this->question(), $this->all()),
        ];
    }

    public function question(): VoiceQuestion
    {
        $question = $this->route('question');

        return $question instanceof VoiceQuestion ? $question : VoiceQuestion::from((string) $question);
    }
}
