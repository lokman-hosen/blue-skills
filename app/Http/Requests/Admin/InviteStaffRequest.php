<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageStaff') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            // Any staff role, built-in or created by the Superuser (D-113).
            'role' => ['required', 'string', Rule::in(app(RoleDirectory::class)->namesFor(RoleKind::Staff))],
        ];
    }
}
