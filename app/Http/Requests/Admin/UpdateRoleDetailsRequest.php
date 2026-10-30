<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateRoleDetailsRequest extends FormRequest
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
            'label' => ['required', 'string', 'min:2', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
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
