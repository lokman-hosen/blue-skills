<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWorkerBasicsRequest extends FormRequest
{
    /** Where the worker goes next: answer by voice, answer by typing, or stop for now. */
    public const INTENTS = ['voice', 'type', 'later'];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'occupation_id' => ['required', 'string', Rule::exists('occupations', 'id')],
            'specialization_id' => [
                'nullable', 'string',
                Rule::exists('specializations', 'id')->where('occupation_id', $this->string('occupation_id')->toString()),
            ],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'intent' => ['required', Rule::in(self::INTENTS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'occupation_id.required' => __('worker.basics.trade_required'),
            'years_experience.required' => __('worker.basics.years_required'),
            'years_experience.max' => __('worker.basics.years_max'),
        ];
    }
}
