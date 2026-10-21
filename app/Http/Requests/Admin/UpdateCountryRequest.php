<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'enabled_as_source' => ['boolean'],
            'enabled_as_destination' => ['boolean'],
            'legal_reviewed' => ['boolean'],
        ];
    }
}
