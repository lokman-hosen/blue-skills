<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'countries' => ['present', 'array', 'max:20'],
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
        ];
    }
}
