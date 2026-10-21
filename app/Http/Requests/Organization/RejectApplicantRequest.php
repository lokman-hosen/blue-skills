<?php

namespace App\Http\Requests\Organization;

use App\Domain\Applications\Enums\RejectionReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectApplicantRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(RejectionReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
