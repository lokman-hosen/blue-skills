<?php

namespace App\Http\Requests\Admin;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Organization\Enums\OrganizationType;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'organization_type' => ['required', Rule::in([OrganizationType::Employer->value, OrganizationType::Agency->value, OrganizationType::Institute->value])],
            'price' => ['required', 'decimal:0,3', 'min:0', 'max:10000000'],
            'price_currency' => ['required', 'string', 'size:3', 'alpha'],
            'billing_period' => ['required', Rule::enum(BillingPeriod::class)],
            'posting_credits' => ['required', 'integer', 'min:0', 'max:100000'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'talent_search' => ['boolean'],
            'recruitment_projects' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array{name: string, description: ?string, organization_type: string, price_amount_minor: int, price_currency: string, billing_period: string, posting_credits: int, seats: ?int, talent_search: bool, recruitment_projects: bool, is_active: bool}
     */
    public function plan(): array
    {
        $currency = strtoupper($this->string('price_currency')->toString());

        return [
            'name' => $this->string('name')->trim()->toString(),
            'description' => $this->string('description')->trim()->toString() ?: null,
            'organization_type' => $this->string('organization_type')->toString(),
            'price_amount_minor' => Money::parseMinor($this->string('price')->toString(), $currency),
            'price_currency' => $currency,
            'billing_period' => $this->string('billing_period')->toString(),
            'posting_credits' => $this->integer('posting_credits'),
            'seats' => $this->filled('seats') ? $this->integer('seats') : null,
            'talent_search' => $this->boolean('talent_search'),
            'recruitment_projects' => $this->boolean('recruitment_projects'),
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
