<?php

namespace App\Http\Requests\Organization;

use App\Domain\Config\Models\Country;
use App\Domain\Organization\Data\RegisterOrganizationData;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'legal_name' => ['required', 'string', 'max:200'],
            'display_name' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', Rule::exists(Country::class, 'code')],
            'city' => ['required', 'string', 'max:120'],
            'registration_number' => ['required', 'string', 'max:80'],
            'licence_number' => ['nullable', 'required_if:type,agency', 'string', 'max:80'],
            'licence_expires_on' => ['nullable', 'required_if:type,agency', 'date', 'after:today'],
            'title' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function toData(): RegisterOrganizationData
    {
        return new RegisterOrganizationData(
            type: OrganizationType::from($this->string('type')->toString()),
            legalName: $this->string('legal_name')->trim()->toString(),
            displayName: $this->string('display_name')->trim()->toString(),
            countryCode: $this->string('country_code')->toString(),
            city: $this->string('city')->trim()->toString(),
            registrationNumber: $this->string('registration_number')->trim()->toString(),
            licenceNumber: $this->filled('licence_number') ? $this->string('licence_number')->trim()->toString() : null,
            licenceExpiresOn: $this->filled('licence_expires_on') ? $this->string('licence_expires_on')->toString() : null,
            title: $this->filled('title') ? $this->string('title')->trim()->toString() : null,
        );
    }
}
