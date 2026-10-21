<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddCaseNoteRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'files' => ['array', 'max:5'],
            'files.*' => ['file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
        ];
    }
}
