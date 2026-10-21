<?php

namespace App\Http\Requests\Organization;

use App\Domain\Mobility\Enums\CaseClosedReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseMobilityCaseRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(CaseClosedReason::class)],
            'note' => ['required_if:reason,other', 'nullable', 'string', 'max:500'],
        ];
    }
}
