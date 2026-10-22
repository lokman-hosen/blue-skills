<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Jobs\Data\HiringAnalyticsData;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hiring analytics for employers and agencies (spec §14 "Analytics"): counts and timings for a
 * date range, never scores.
 */
class AnalyticsController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAnalytics', $organization);

        $to = CarbonImmutable::make($request->date('to')) ?? CarbonImmutable::today();
        $from = CarbonImmutable::make($request->date('from')) ?? $to->subDays(89);
        if ($from->greaterThan($to)) {
            $from = $to;
        }

        return Inertia::render('org/analytics', [
            'analytics' => HiringAnalyticsData::for($organization, $from, $to),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
