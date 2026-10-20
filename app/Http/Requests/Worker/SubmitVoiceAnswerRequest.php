<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A recorded answer (`audio`) or a typed one (`text`), never both.
 */
class SubmitVoiceAnswerRequest extends FormRequest
{
    /** Browsers record WebM/Opus (Chrome, Firefox) or MP4/AAC (Safari). */
    public const AUDIO_MIME_TYPES = [
        'audio/webm', 'video/webm', 'audio/ogg', 'audio/mp4', 'video/mp4', 'audio/aac',
        'audio/x-m4a', 'audio/mpeg', 'audio/wav', 'audio/x-wav',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'audio' => [
                'required_without:text', 'prohibits:text', 'file',
                'mimetypes:'.implode(',', self::AUDIO_MIME_TYPES),
                'max:'.config('blueskills.voice.max_recording_kb'),
            ],
            'text' => ['required_without:audio', 'string', 'max:'.config('blueskills.voice.max_typed_characters')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio.mimetypes' => __('worker.voice.audio_type'),
            'audio.max' => __('worker.voice.audio_size'),
            'text.required_without' => __('worker.voice.text_required'),
            'text.max' => __('worker.voice.text_max', ['max' => config('blueskills.voice.max_typed_characters')]),
        ];
    }
}
