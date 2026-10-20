<?php

namespace App\Http\Requests\Public;

use App\Domain\Trust\Enums\ReportCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportJobRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in([
                ReportCategory::FakeJob->value, ReportCategory::UnauthorizedPayment->value,
                ReportCategory::MisleadingSalary->value, ReportCategory::Impersonation->value,
                ReportCategory::Other->value,
            ])],
            'description' => ['required_if:category,other', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['description.required_if' => __('jobs.report.describe')];
    }
}
