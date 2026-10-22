<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Withdrawn;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Counts and timings for an organization's hiring analytics (spec §14 "Analytics"). Counts only:
 * no scores and no ranking of people (product rule 12).
 */
final class HiringAnalytics
{
    private const SHORTLISTED = ['shortlisted', 'interview', 'trade_assessment', 'selected', 'offer', 'accepted', 'declined'];

    private const OFFERED = ['offer', 'accepted', 'declined'];

    /**
     * Median days from applying to first reaching a shortlist and an offer, for applications made
     * in the range. Null when nobody got there yet.
     *
     * @param  list<string>  $jobIds
     * @return array{shortlist: ?float, offer: ?float}
     */
    public static function medianDays(array $jobIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $median = function (array $statuses) use ($jobIds, $from, $to): ?float {
            if ($jobIds === []) {
                return null;
            }

            $firstReached = DB::table('application_stage_events')
                ->whereIn('to_status', $statuses)
                ->groupBy('application_id')
                ->selectRaw('application_id, min(created_at) as reached_at');

            $value = DB::table('applications')
                ->joinSub($firstReached, 'reached', 'reached.application_id', '=', 'applications.id')
                ->whereIn('applications.job_id', $jobIds)
                ->whereBetween('applications.applied_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->selectRaw('percentile_cont(0.5) within group (order by extract(epoch from (reached.reached_at - applications.applied_at)) / 86400) as median')
                ->value('median');

            return $value !== null ? round(max(0, (float) $value), 1) : null;
        };

        return ['shortlist' => $median(self::SHORTLISTED), 'offer' => $median(self::OFFERED)];
    }

    /**
     * Applicants by the country they live in now, largest first.
     *
     * @param  list<string>  $jobIds
     * @return list<array{code: string, count: int}>
     */
    public static function countries(array $jobIds, CarbonInterface $from, CarbonInterface $to, int $limit = 8): array
    {
        return self::applications($jobIds, $from, $to)
            ->join('worker_profiles', 'worker_profiles.id', '=', 'applications.worker_profile_id')
            ->whereNotNull('worker_profiles.current_country_code')
            ->groupBy('worker_profiles.current_country_code')
            ->selectRaw('worker_profiles.current_country_code as code, count(*) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['code' => (string) $row->getAttribute('code'), 'count' => (int) $row->getAttribute('total')])
            ->values()
            ->all();
    }

    /**
     * How applicants arrived: invited from talent search or a pool, or applied on their own.
     *
     * @param  list<string>  $jobIds
     * @return array{invited: int, direct: int, withdrawn: int}
     */
    public static function sources(array $jobIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $total = self::applications($jobIds, $from, $to)->count();
        $invited = self::applications($jobIds, $from, $to)
            ->whereExists(fn ($query) => $query->from('job_invitations')
                ->whereColumn('job_invitations.job_id', 'applications.job_id')
                ->whereColumn('job_invitations.worker_profile_id', 'applications.worker_profile_id'))
            ->count();

        return [
            'invited' => $invited,
            'direct' => $total - $invited,
            'withdrawn' => self::applications($jobIds, $from, $to)->where('status', Withdrawn::$name)->count(),
        ];
    }

    /**
     * @param  list<string>  $jobIds
     * @return Builder<Application>
     */
    private static function applications(array $jobIds, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Application::query()
            ->whereIn('applications.job_id', $jobIds)
            ->whereBetween('applications.applied_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }
}
