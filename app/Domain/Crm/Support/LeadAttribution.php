<?php

namespace App\Domain\Crm\Support;

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Institute\Models\Cohort;
use App\Http\Controllers\Public\CohortInviteController;
use Illuminate\Contracts\Session\Session;

/**
 * Where a new worker came from: the campaign link they last opened in this browser session, an
 * institute invite, or neither (D-096).
 */
final readonly class LeadAttribution
{
    public const SESSION_KEY = 'crm_campaign';

    public function __construct(
        public LeadSource $source,
        public ?string $campaignId = null,
        public ?string $partnerOrganizationId = null,
    ) {}

    public static function direct(): self
    {
        return new self(LeadSource::Direct);
    }

    public static function fromSession(Session $session): self
    {
        $campaignId = $session->get(self::SESSION_KEY);
        $campaign = is_string($campaignId) ? Campaign::query()->find($campaignId) : null;

        if ($campaign !== null) {
            return new self(LeadSource::Campaign, $campaign->id, $campaign->partner_organization_id);
        }

        $inviteCode = $session->get(CohortInviteController::SESSION_KEY);
        $cohort = is_string($inviteCode) ? Cohort::query()->where('invite_code', $inviteCode)->first() : null;

        if ($cohort !== null) {
            return new self(LeadSource::Institute, null, $cohort->organization_id);
        }

        return self::direct();
    }
}
