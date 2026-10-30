<?php

namespace App\Http\Requests\Organization;

use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRoleRequest extends FormRequest
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
            // Any organization role, built-in or created (D-113); the action checks it fits the organization.
            'role' => ['required', 'string', Rule::in($this->organizationRoles())],
        ];
    }

    /**
     * @return list<string>
     */
    private function organizationRoles(): array
    {
        $directory = app(RoleDirectory::class);

        return [
            ...$directory->namesFor(RoleKind::Employer),
            ...$directory->namesFor(RoleKind::Agency),
            ...$directory->namesFor(RoleKind::Institute),
        ];
    }
}
