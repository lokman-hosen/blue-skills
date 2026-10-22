<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Campaign;
use App\Domain\Identity\Models\User;

/**
 * Creates or edits a campaign. The code is set from the name once and never changes, so printed
 * QR codes and shared links keep working.
 */
class SaveCampaign
{
    /**
     * @param  array{name: string, channel: string, source_country_code: ?string, partner_organization_id: ?string, landing: string, starts_on: string, ends_on: ?string}  $attributes
     */
    public function handle(?Campaign $campaign, array $attributes, User $agent): Campaign
    {
        if ($campaign === null) {
            $campaign = Campaign::query()->create([
                ...$attributes,
                'code' => Campaign::newCode($attributes['name']),
                'created_by' => $agent->id,
            ]);
            activity('crm')->performedOn($campaign)->causedBy($agent)->log('campaign.created');

            return $campaign;
        }

        $campaign->update($attributes);

        return $campaign;
    }
}
