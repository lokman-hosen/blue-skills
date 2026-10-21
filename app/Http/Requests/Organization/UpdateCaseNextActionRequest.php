<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseNextActionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'next_action' => ['nullable', 'string', 'max:500'],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
