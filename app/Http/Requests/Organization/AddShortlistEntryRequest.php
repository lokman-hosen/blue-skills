<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddShortlistEntryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The worker's public Passport number, e.g. BSK-0004182.
            'worker' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
