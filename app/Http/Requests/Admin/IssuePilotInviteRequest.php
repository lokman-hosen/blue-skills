<?php

namespace App\Http\Requests\Admin;

use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssuePilotInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', Organization::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'partner_name' => ['required', 'string', 'max:160'],
            'organization_type' => ['nullable', Rule::enum(OrganizationType::class)],
            'max_uses' => ['required', 'integer', 'min:1', 'max:50'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
