<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddPoolMembersRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profiles' => ['required', 'array', 'min:1', 'max:200'],
            'profiles.*' => ['required', 'string', 'size:26'],
        ];
    }
}
