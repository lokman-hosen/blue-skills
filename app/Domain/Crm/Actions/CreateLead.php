<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Models\User;

/**
 * A CRM agent enters someone met at a job fair, on the phone or through a referral (spec §9).
 * When they later sign up with the same number, the lead is linked to their account.
 */
class CreateLead
{
    public function handle(string $name, string $phone, ?string $countryCode, LeadSource $source, ?string $campaignId, ?string $assignedTo, User $agent): Lead
    {
        $lead = Lead::query()->create([
            'name' => $name,
            'phone' => $phone,
            'country_code' => $countryCode,
            'source' => $source,
            'campaign_id' => $campaignId,
            'assigned_to' => $assignedTo,
            'follow_up_status' => FollowUpStatus::New,
            'conversion_stage' => ConversionStage::Prospect,
            'created_by' => $agent->id,
        ]);

        activity('crm')->performedOn($lead)->causedBy($agent)->log('lead.created');

        return $lead;
    }
}
