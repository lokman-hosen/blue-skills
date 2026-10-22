<?php

namespace App\Http\Requests\Admin;

use App\Domain\Crm\Enums\CampaignChannel;
use App\Domain\Crm\Enums\CampaignLanding;
use App\Domain\Organization\Enums\OrganizationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCampaignRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'source_country_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
            'partner_organization_id' => ['nullable', 'string', Rule::exists('organizations', 'id')
                ->whereIn('type', [OrganizationType::Institute->value, OrganizationType::Agency->value])],
            'landing' => ['required', Rule::enum(CampaignLanding::class)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array{name: string, channel: string, source_country_code: ?string, partner_organization_id: ?string, landing: string, starts_on: string, ends_on: ?string}
     */
    public function campaign(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'channel' => $this->string('channel')->toString(),
            'source_country_code' => $this->string('source_country_code')->toString() ?: null,
            'partner_organization_id' => $this->string('partner_organization_id')->toString() ?: null,
            'landing' => $this->string('landing')->toString(),
            'starts_on' => $this->string('starts_on')->toString(),
            'ends_on' => $this->string('ends_on')->toString() ?: null,
        ];
    }
}
