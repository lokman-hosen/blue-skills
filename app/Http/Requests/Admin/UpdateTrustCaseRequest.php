<?php

namespace App\Http\Requests\Admin;

use App\Domain\Trust\Enums\CasePriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrustCaseRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'string', 'exists:users,id'],
            'priority' => ['required', Rule::enum(CasePriority::class)],
            'investigate' => ['boolean'],
        ];
    }
}
