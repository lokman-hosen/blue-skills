<?php

namespace App\Http\Requests\Admin;

use App\Domain\Trust\Enums\TrustReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseTrustCaseRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(TrustReason::class)],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
