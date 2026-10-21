<?php

namespace App\Http\Requests\Shared;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class SendMessageRequest extends FormRequest
{
    public const MAX_KB = 10_240;

    public const MAX_FILES = 3;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => ['file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.self::MAX_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => __('messages.too_many_files', ['max' => self::MAX_FILES]),
            'attachments.*.mimetypes' => __('worker.credential.type_error'),
            'attachments.*.max' => __('worker.credential.size_error', ['max' => self::MAX_KB / 1024]),
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function attachments(): array
    {
        return array_values(array_filter((array) $this->file('attachments', []), fn ($file) => $file instanceof UploadedFile));
    }
}
