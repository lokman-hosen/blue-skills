<?php

namespace App\Domain\Crm\Support;

use App\Domain\Crm\Data\FunnelData;
use App\Domain\Crm\Models\CampaignVisit;
use App\Domain\Crm\Models\Lead;
use App\Domain\Worker\Models\WorkerProfile;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds campaign funnels from leads created in a date range (spec §9). The stage definitions are
 * LeadProgress's, so the funnel and the CRM always agree (D-097). One grouped query per step, not
 * per campaign.
 */
final class CampaignFunnel
{
    /** Key for leads that came without a campaign (direct sign-ups and institute invites). */
    public const NO_CAMPAIGN = '';

    /**
     * @param  list<string>  $campaignIds
     * @return array<string, FunnelData> keyed by campaign ID, plus NO_CAMPAIGN when asked for
     */
    public static function for(array $campaignIds, CarbonInterface $from, CarbonInterface $to, bool $withoutCampaign = false): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $visitors = CampaignVisit::query()
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->groupBy('campaign_id')
            ->selectRaw('campaign_id, sum(visits) as total')
            ->pluck('total', 'campaign_id');

        $scope = function (Builder $query, string $column) use ($campaignIds, $withoutCampaign): void {
            $query->where(function (Builder $match) use ($column, $campaignIds, $withoutCampaign): void {
                $match->whereIn($column, $campaignIds);
                if ($withoutCampaign) {
                    $match->orWhereNull($column);
                }
            });
        };

        $leads = Lead::query()
            ->whereBetween('created_at', [$start, $end])
            ->tap(fn (Builder $query) => $scope($query, 'campaign_id'))
            ->groupBy('campaign_id')
            ->selectRaw('campaign_id, count(*) as total, count(user_id) as registered')
            ->get()
            ->keyBy(fn (Lead $lead) => (string) $lead->campaign_id);

        $count = function (Closure $step) use ($start, $end, $scope) {
            $profiles = WorkerProfile::query()
                ->join('leads', 'leads.user_id', '=', 'worker_profiles.user_id')
                ->whereBetween('leads.created_at', [$start, $end])
                ->tap(fn (Builder $query) => $scope($query, 'leads.campaign_id'));

            return $step($profiles)->groupBy('leads.campaign_id')
                ->selectRaw('leads.campaign_id as campaign_key, count(distinct worker_profiles.id) as total')
                ->pluck('total', 'campaign_key')
                ->mapWithKeys(fn ($total, $key) => [(string) $key => (int) $total]);
        };

        $complete = $count(fn (Builder $query) => LeadProgress::profileComplete($query));
        $qualified = $count(fn (Builder $query) => LeadProgress::qualified($query));
        $applied = $count(fn (Builder $query) => LeadProgress::applied($query));
        $selected = $count(fn (Builder $query) => LeadProgress::selected($query));
        $deployed = $count(fn (Builder $query) => LeadProgress::deployed($query));

        $keys = $withoutCampaign ? [...$campaignIds, self::NO_CAMPAIGN] : $campaignIds;
        $funnels = [];

        foreach ($keys as $key) {
            $row = $leads->get($key);
            $funnels[$key] = new FunnelData(
                visitors: (int) ($visitors[$key] ?? 0),
                leads: (int) ($row?->getAttribute('total') ?? 0),
                registrations: (int) ($row?->getAttribute('registered') ?? 0),
                profilesComplete: $complete[$key] ?? 0,
                qualified: $qualified[$key] ?? 0,
                applied: $applied[$key] ?? 0,
                selected: $selected[$key] ?? 0,
                deployed: $deployed[$key] ?? 0,
            );
        }

        return $funnels;
    }
}
