<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnswerCaseContractRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Sending the contract back says what needs changing.
        return [
            'note' => [$this->route('answer') === 'query' ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }
}
