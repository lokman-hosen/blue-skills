<?php

namespace App\Http\Requests\Admin;

use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Verification\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCountryRulesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agency_eligibility' => ['nullable', 'string', 'max:500'],
            'worker_cost_policy' => ['nullable', 'string', 'max:500'],
            'local_notice' => ['nullable', 'string', 'max:1000'],
            'languages' => ['array'],
            'languages.*' => ['string', 'exists:languages,code'],
            'allowed_worker_cost_categories' => ['array'],
            'allowed_worker_cost_categories.*' => [Rule::enum(JobCostCategory::class)],
            'mandatory_disclosures' => ['array'],
            'mandatory_disclosures.*' => [Rule::in(array_keys(JobCompliance::DISCLOSURES))],
            'document_types' => ['array'],
            'document_types.*' => [Rule::enum(DocumentType::class)],
            'mobility_stage_template' => ['array', 'max:20'],
            'mobility_stage_template.*.key' => ['nullable', 'string', 'max:48', 'regex:/^[a-z0-9_]*$/'],
            'mobility_stage_template.*.label' => ['required', 'string', 'max:80'],
            'mobility_stage_template.*.done_on_open' => ['boolean'],
        ];
    }
}
