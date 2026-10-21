<?php

namespace App\Http\Requests\Admin;

use App\Domain\Trust\Enums\TrustReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrustActionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(TrustReason::class)],
            'note' => ['required', 'string', 'max:1000'],
        ];
    }
}
