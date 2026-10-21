<?php

namespace App\Http\Requests\Organization;

use App\Domain\Institute\Enums\AssessmentResult;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordAssessmentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'result' => ['required', Rule::enum(AssessmentResult::class)],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'assessed_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
