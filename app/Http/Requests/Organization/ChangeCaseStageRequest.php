<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeCaseStageRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // A blocked or skipped stage tells the worker why.
        $needsNote = in_array($this->route('action'), ['block', 'skip'], true);

        return [
            'note' => [$needsNote ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }
}
