<?php

namespace App\Http\Requests\Admin;

use App\Domain\Trust\Enums\CasePriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenTrustCaseRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_id' => ['required', 'string', 'exists:reports,id'],
            'priority' => ['required', Rule::enum(CasePriority::class)],
            'summary' => ['required', 'string', 'max:500'],
        ];
    }
}
