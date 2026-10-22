<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Support\LeadAttribution;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Someone opened a campaign link. Running campaigns count one visit per browser session per day
 * (a count only, D-095) and remember the campaign so a sign-up is attributed to it. Paused and
 * ended campaigns still lead to their page but count and attribute nothing.
 */
class RecordCampaignVisit
{
    public function handle(Campaign $campaign, Session $session, bool $signedIn): void
    {
        if (! $campaign->isActive() || $campaign->starts_on->isFuture() || ($campaign->ends_on?->endOfDay()->isPast() ?? false)) {
            return;
        }

        if (! $signedIn) {
            $session->put(LeadAttribution::SESSION_KEY, $campaign->id);
        }

        $day = now()->toDateString();
        $seenKey = 'crm_campaign_seen.'.$campaign->id;

        if ($session->get($seenKey) === $day) {
            return;
        }

        $session->put($seenKey, $day);

        DB::table('campaign_visits')->upsert(
            [['id' => (string) Str::ulid(), 'campaign_id' => $campaign->id, 'day' => $day, 'visits' => 1]],
            ['campaign_id', 'day'],
            ['visits' => DB::raw('campaign_visits.visits + 1')],
        );
    }
}
