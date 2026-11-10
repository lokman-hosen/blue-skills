<?php

namespace App\Http\Requests\Organization;

use App\Domain\Jobs\Support\JobWizard;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveJobStepRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = JobWizard::rules($this->step(), $this->all());

        if ($this->step() === 'basics') {
            $user = $this->user();
            $organization = $user !== null ? app(CurrentOrganization::class)->resolve($user, $this->session()) : null;

            // Agencies recruit under one of their own mandates (product rule 10, D-077).
            $rules['job_mandate_id'] = $organization?->type === OrganizationType::Agency
                ? ['required', 'string', Rule::exists('job_mandates', 'id')->where('agency_organization_id', $organization->id)]
                : ['prohibited'];
        }

        return $rules;
    }

    /**
     * The same wording the wizard shows before it sends the step (D-117).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $other = __('jobs.validation.other_required');

        return [
            'working_hours_other.required_if' => $other,
            'working_days_other.required_if' => $other,
            'shift_pattern_other.required_if' => $other,
            'experience_other.required_if' => $other,
            'education_other.required_if' => $other,
            'age_other.required_if' => $other,
            'contract_other.required_if' => $other,
            'accommodation_cost_amount.required' => __('jobs.validation.accommodation_cost_required'),
            'salary_max_amount.gte' => __('jobs.validation.salary_max_below_min'),
            'salary_amount.required_with' => __('jobs.validation.salary_min_required'),
            'salary_currency.required_with' => __('jobs.validation.currency_required'),
            'apply_url.required_if' => __('jobs.validation.apply_url_required'),
            'apply_url.url' => __('jobs.validation.apply_url_invalid'),
        ];
    }

    /**
     * Creating a job always starts with the basics step.
     */
    public function step(): string
    {
        $step = $this->route('step');

        return is_string($step) ? $step : 'basics';
    }
}
