<?php

namespace App\Http\Requests\Worker;

use App\Domain\Config\Models\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteWorkerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'country' => ['required', 'string', Rule::exists(Country::class, 'code')->where('enabled_as_source', true)],
        ];
    }
}
