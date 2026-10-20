<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkerPhotoRequest extends FormRequest
{
    public const MAX_KB = 5_120;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.self::MAX_KB, 'dimensions:min_width=200,min_height=200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.mimetypes' => __('worker.photo.type'),
            'photo.max' => __('worker.photo.size', ['max' => self::MAX_KB / 1024]),
            'photo.dimensions' => __('worker.photo.dimensions'),
        ];
    }
}
