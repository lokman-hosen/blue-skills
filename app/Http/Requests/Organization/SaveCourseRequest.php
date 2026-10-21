<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveCourseRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'occupation_id' => ['nullable', 'string', 'exists:occupations,id'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:520'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
