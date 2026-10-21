<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendOfferRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Whole units of the job's currency (D-056), stored as amount_minor.
            'salary_amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'start_on' => ['nullable', 'date', 'after:today'],
            'respond_by' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
