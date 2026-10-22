<?php

namespace App\Http\Controllers\Public;

use App\Domain\Crm\Actions\RecordCampaignVisit;
use App\Domain\Crm\Enums\CampaignLanding;
use App\Domain\Crm\Models\Campaign;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A campaign link /c/{code} (spec §9): counts the visit, remembers the campaign for sign-up, and
 * sends people to the sign-up or job search page. An unknown code goes to the home page.
 */
class CampaignLinkController extends Controller
{
    public function show(Request $request, string $code, RecordCampaignVisit $recordVisit): RedirectResponse
    {
        $campaign = Campaign::query()->where('code', strtoupper($code))->first();

        if ($campaign === null) {
            return to_route('home');
        }

        $recordVisit->handle($campaign, $request->session(), $request->user() !== null);

        return $campaign->landing === CampaignLanding::Jobs || $request->user() !== null
            ? to_route('jobs.index')
            : to_route('worker.join');
    }
}
