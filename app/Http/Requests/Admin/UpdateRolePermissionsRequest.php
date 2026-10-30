<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Support\PermissionCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
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
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(app(PermissionCatalog::class)->all())],
        ];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_values(array_map('strval', $this->array('permissions')));
    }
}
