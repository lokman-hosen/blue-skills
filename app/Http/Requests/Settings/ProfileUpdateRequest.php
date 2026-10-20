<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Workers sign in by phone; their email is managed with the Skill Passport (Module 2).
        if ($this->user()->isWorker()) {
            return ['name' => $this->nameRules()];
        }

        return $this->profileRules($this->user()->id);
    }
}
