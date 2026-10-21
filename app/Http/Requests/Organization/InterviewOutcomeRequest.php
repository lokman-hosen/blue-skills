<?php

namespace App\Http\Requests\Organization;

use App\Domain\Applications\Enums\InterviewOutcome;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterviewOutcomeRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::enum(InterviewOutcome::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
