<?php

namespace App\Http\Requests\Admin;

use App\Domain\Crm\Enums\LeadSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class StoreLeadRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'size:2', Rule::in(config('blueskills.worker_phone_countries'))],
            'phone' => ['required', 'string', 'max:20', (new Phone)->countryField('country')->mobile()],
            'source' => ['required', Rule::in([LeadSource::WalkIn->value, LeadSource::CallCentre->value, LeadSource::Referral->value, LeadSource::Campaign->value])],
            'campaign_id' => ['nullable', 'required_if:source,campaign', 'string', 'exists:campaigns,id'],
            'assign_to_me' => ['boolean'],
        ];
    }
}
