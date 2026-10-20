<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;
use Propaganistas\LaravelPhone\Rules\Phone;

class RequestPhoneCodeRequest extends FormRequest
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
            'country' => ['required', 'string', Rule::in(config('blueskills.worker_phone_countries'))],
            'phone' => ['required', 'string', 'max:20', (new Phone)->countryField('country')->mobile()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.phone' => __('auth.phone.invalid'),
        ];
    }

    public function e164(): string
    {
        return (new PhoneNumber($this->string('phone')->toString(), $this->string('country')->toString()))->formatE164();
    }
}
