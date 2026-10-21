<?php

namespace App\Http\Requests\Organization;

use App\Domain\Jobs\Support\JobWizard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveJobStepRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return JobWizard::rules($this->step(), $this->all());
    }

    /**
     * Creating a job always starts with the requirement step.
     */
    public function step(): string
    {
        $step = $this->route('step');

        return is_string($step) ? $step : 'requirement';
    }
}
