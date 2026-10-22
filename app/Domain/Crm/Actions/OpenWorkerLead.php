<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\LeadAttribution;
use App\Domain\Identity\Models\User;

/**
 * Every worker who signs up becomes a lead, tied to the campaign or institute that brought them
 * (spec §9). A hand-entered lead with the same phone number is linked instead of duplicated.
 */
class OpenWorkerLead
{
    public function handle(User $worker, LeadAttribution $attribution): Lead
    {
        $existing = $worker->lead()->first();

        if ($existing !== null) {
            return $existing;
        }

        $handEntered = $worker->phone !== null
            ? Lead::query()->whereNull('user_id')->where('phone', $worker->phone)->oldest()->first()
            : null;

        if ($handEntered !== null) {
            $handEntered->forceFill([
                'user_id' => $worker->id,
                'conversion_stage' => ConversionStage::Registered,
                'stage_reached_at' => now(),
            ])->save();

            return $handEntered;
        }

        return Lead::query()->create([
            'user_id' => $worker->id,
            'source' => $attribution->source,
            'campaign_id' => $attribution->campaignId,
            'partner_organization_id' => $attribution->partnerOrganizationId,
            'country_code' => $worker->workerProfile?->current_country_code,
            'follow_up_status' => FollowUpStatus::New,
            'conversion_stage' => ConversionStage::Registered,
            'stage_reached_at' => now(),
        ]);
    }
}
