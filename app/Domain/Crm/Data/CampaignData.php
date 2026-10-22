<?php

namespace App\Domain\Crm\Data;

use App\Domain\Crm\Enums\CampaignChannel;
use App\Domain\Crm\Enums\CampaignLanding;
use App\Domain\Crm\Models\Campaign;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class CampaignData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public CampaignChannel $channel,
        public ?string $sourceCountryCode,
        public ?string $partnerId,
        public ?string $partner,
        public CampaignLanding $landing,
        /** Translated via `status.campaign_{value}`. */
        public StatusData $status,
        public string $startsOn,
        public ?string $endsOn,
        public string $link,
        public FunnelData $funnel,
    ) {}

    public static function fromCampaign(Campaign $campaign, FunnelData $funnel): self
    {
        /** @var string $status */
        $status = $campaign->status->getValue();

        return new self(
            id: $campaign->id,
            code: $campaign->code,
            name: $campaign->name,
            channel: $campaign->channel,
            sourceCountryCode: $campaign->source_country_code,
            partnerId: $campaign->partner_organization_id,
            partner: $campaign->partner?->display_name,
            landing: $campaign->landing,
            status: new StatusData('campaign_'.$status, $campaign->status->label(), $campaign->status->tone()),
            startsOn: $campaign->starts_on->toDateString(),
            endsOn: $campaign->ends_on?->toDateString(),
            link: route('campaign-link.show', $campaign->code),
            funnel: $funnel,
        );
    }
}
