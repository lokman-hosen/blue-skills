<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Crm\Actions\ChangeCampaignStatus;
use App\Domain\Crm\Actions\SaveCampaign;
use App\Domain\Crm\Data\CampaignData;
use App\Domain\Crm\Data\FunnelData;
use App\Domain\Crm\Enums\CampaignChannel;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Support\CampaignFunnel;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Verified;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCampaignRequest;
use App\Support\QrCode;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Marketing campaigns and their funnels (spec §9 "Campaign analytics", spec §14 "Marketing").
 */
class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Campaign::class);
        [$from, $to] = $this->range($request);

        $campaigns = Campaign::query()->with('partner')->orderByDesc('starts_on')->orderBy('name')->get();
        $funnels = CampaignFunnel::for($campaigns->modelKeys(), $from, $to, withoutCampaign: true);
        $rows = $campaigns->map(fn (Campaign $campaign) => CampaignData::fromCampaign($campaign, $funnels[$campaign->id]))->values()->all();

        return Inertia::render('admin/marketing/index', [
            'campaigns' => $rows,
            'total' => array_reduce($rows, fn (FunnelData $sum, CampaignData $row) => $sum->plus($row->funnel), new FunnelData),
            'withoutCampaign' => $funnels[CampaignFunnel::NO_CAMPAIGN],
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            ...$this->formOptions(),
        ]);
    }

    public function store(SaveCampaignRequest $request, SaveCampaign $save): RedirectResponse
    {
        Gate::authorize('create', Campaign::class);

        $campaign = $save->handle(null, $request->campaign(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('marketing.done.created', ['code' => $campaign->code])]);

        return to_route('admin.marketing.show', $campaign);
    }

    public function show(Request $request, Campaign $campaign): Response
    {
        Gate::authorize('update', $campaign);
        [$from, $to] = $this->range($request, $campaign);
        $funnel = CampaignFunnel::for([$campaign->id], $from, $to)[$campaign->id];

        return Inertia::render('admin/marketing/show', [
            'campaign' => CampaignData::fromCampaign($campaign->load('partner'), $funnel),
            'visits' => $campaign->visits()->whereBetween('day', [$from->toDateString(), $to->toDateString()])->orderBy('day')
                ->get(['day', 'visits'])->map(fn ($visit) => ['day' => $visit->day->toDateString(), 'visits' => $visit->visits])->values()->all(),
            'qrSvg' => QrCode::svg(route('campaign-link.show', $campaign->code)),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            ...$this->formOptions(),
        ]);
    }

    public function update(SaveCampaignRequest $request, Campaign $campaign, SaveCampaign $save): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $save->handle($campaign, $request->campaign(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('marketing.done.updated')]);

        return to_route('admin.marketing.show', $campaign);
    }

    public function status(Campaign $campaign, string $action, ChangeCampaignStatus $change): RedirectResponse
    {
        Gate::authorize('update', $campaign);
        abort_unless(in_array($action, ChangeCampaignStatus::ACTIONS, true), 404);

        $change->handle($campaign, $action);
        Inertia::flash('toast', ['type' => 'success', 'message' => __("marketing.done.{$action}")]);

        return to_route('admin.marketing.show', $campaign);
    }

    /**
     * The QR code as a file for posters and job fair banners.
     */
    public function qr(Campaign $campaign): HttpResponse
    {
        Gate::authorize('update', $campaign);

        return response('<?xml version="1.0" encoding="UTF-8"?>'."\n".QrCode::svg(route('campaign-link.show', $campaign->code), 1024), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="blueskills-'.strtolower($campaign->code).'.svg"',
        ]);
    }

    /**
     * From/to dates; the last 90 days by default, or a campaign's own dates.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request, ?Campaign $campaign = null): array
    {
        $to = CarbonImmutable::make($request->date('to')) ?? CarbonImmutable::today();
        $default = $campaign !== null ? CarbonImmutable::make($campaign->starts_on) : null;
        $from = CarbonImmutable::make($request->date('from')) ?? $default ?? $to->subDays(89);

        return $from->greaterThan($to) ? [$to, $to] : [$from, $to];
    }

    /**
     * @return array{channels: list<CampaignChannel>, partners: list<array{id: string, name: string}>, countries: list<string>}
     */
    private function formOptions(): array
    {
        return [
            'channels' => CampaignChannel::cases(),
            'partners' => Organization::query()
                ->whereIn('type', [OrganizationType::Institute, OrganizationType::Agency])
                ->whereState('verification_status', Verified::class)
                ->orderBy('display_name')->get(['id', 'display_name'])
                ->map(fn (Organization $organization) => ['id' => $organization->id, 'name' => (string) $organization->display_name])
                ->values()->all(),
            'countries' => config('blueskills.worker_phone_countries'),
        ];
    }
}
