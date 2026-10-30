<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Enums\RoleKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manageRoles');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(RoleKind::class)],
            'label' => ['required', 'string', 'min:2', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'based_on' => ['required', 'string', 'max:64'],
            'copy_permissions' => ['boolean'],
        ];
    }

    public function label(): string
    {
        return $this->string('label')->squish()->toString();
    }

    public function description(): ?string
    {
        return $this->string('description')->trim()->toString() ?: null;
    }
}
