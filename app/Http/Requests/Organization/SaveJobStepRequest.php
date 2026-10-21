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

        if ($this->step() === 'requirement') {
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
     * Creating a job always starts with the requirement step.
     */
    public function step(): string
    {
        $step = $this->route('step');

        return is_string($step) ? $step : 'requirement';
    }
}
