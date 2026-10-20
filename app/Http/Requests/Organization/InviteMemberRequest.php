<?php

namespace App\Http\Requests\Organization;

use App\Domain\Identity\Enums\OrganizationRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TeamController authorizes manageTeam on the current organization
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ];
    }
}
