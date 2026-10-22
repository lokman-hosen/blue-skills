<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Mobility\States\MobilityCase\Open;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hiring stage counts for a set of jobs (deck slide 18: Applied → Qualified → Interview →
 * Selected → Mobility → Joined), used by recruitment projects and hiring analytics. A stage counts
 * everyone who ever reached it or a later one, read from the stage history, so moving someone
 * straight to Selected still counts them as qualified and a later rejection doesn't erase that
 * they were interviewed. One grouped query per stage.
 */
final class HiringStages
{
    public const STAGES = ['applied', 'qualified', 'interview', 'selected', 'mobility', 'joined'];

    /** Statuses at or after each pipeline stage. */
    private const REACHED = [
        'qualified' => ['qualified', 'shortlisted', 'interview', 'trade_assessment', 'selected', 'offer', 'accepted', 'declined'],
        'interview' => ['interview', 'trade_assessment', 'selected', 'offer', 'accepted', 'declined'],
        'selected' => ['selected', 'offer', 'accepted', 'declined'],
    ];

    /**
     * Counts per job, for applications made in the date range when one is given.
     *
     * @param  list<string>  $jobIds
     * @return array<string, array<string, int>> job ID => stage => count
     */
    public static function byJob(array $jobIds, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $applications = fn () => Application::query()
            ->whereIn('job_id', $jobIds)
            ->when($from !== null && $to !== null, fn (Builder $query) => $query
                ->whereBetween('applied_at', [$from?->copy()->startOfDay(), $to?->copy()->endOfDay()]));
        $grouped = fn (Builder $query) => $query->groupBy('job_id')->selectRaw('job_id, count(*) as total')
            ->pluck('total', 'job_id')->map(fn ($total) => (int) $total);
        $reached = fn (array $statuses) => $grouped($applications()->where(fn (Builder $query) => $query
            ->whereIn('status', $statuses)
            ->orWhereHas('stageEvents', fn (Builder $events) => $events->whereIn('to_status', $statuses))));

        $counts = [
            'applied' => $grouped($applications()),
            'qualified' => $reached(self::REACHED['qualified']),
            'interview' => $reached(self::REACHED['interview']),
            'selected' => $reached(self::REACHED['selected']),
            'mobility' => $grouped($applications()->whereHas('mobilityCase', fn (Builder $case) => $case->whereIn('status', [Open::$name, Completed::$name]))),
            'joined' => $grouped($applications()->whereHas('mobilityCase', fn (Builder $case) => $case->where('status', Completed::$name))),
        ];

        $byJob = [];
        foreach ($jobIds as $jobId) {
            foreach (self::STAGES as $stage) {
                $byJob[$jobId][$stage] = $counts[$stage][$jobId] ?? 0;
            }
        }

        return $byJob;
    }

    /**
     * @param  list<string>  $jobIds
     * @return array<string, int> keyed by STAGES
     */
    public static function totals(array $jobIds, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $totals = array_fill_keys(self::STAGES, 0);

        foreach (self::byJob($jobIds, $from, $to) as $counts) {
            foreach ($counts as $stage => $count) {
                $totals[$stage] += $count;
            }
        }

        return $totals;
    }

    /**
     * @param  list<string>  $jobIds
     */
    public static function joined(array $jobIds): int
    {
        return Application::query()->whereIn('job_id', $jobIds)
            ->whereHas('mobilityCase', fn (Builder $case) => $case->where('status', Completed::$name))
            ->count();
    }
}
