<?php

namespace App\Http\Requests\Admin;

use App\Domain\Trust\Enums\TrustReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DismissReportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(TrustReason::class)->only(TrustReason::forDismissal())],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
