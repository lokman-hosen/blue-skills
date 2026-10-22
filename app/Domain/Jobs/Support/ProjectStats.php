<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Mobility\States\MobilityCase\Open;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stage counts for a set of jobs (deck slide 18: Applied → Qualified → Interview → Selected →
 * Mobility → Joined). A stage counts everyone who ever reached it or a later one, read from the
 * stage history, so moving someone straight to Selected still counts them as qualified and a
 * later rejection doesn't erase that they were interviewed.
 */
final class ProjectStats
{
    public const STAGES = ['applied', 'qualified', 'interview', 'selected', 'mobility', 'joined'];

    /** Statuses at or after each pipeline stage. */
    private const REACHED = [
        'qualified' => ['qualified', 'shortlisted', 'interview', 'trade_assessment', 'selected', 'offer', 'accepted', 'declined'],
        'interview' => ['interview', 'trade_assessment', 'selected', 'offer', 'accepted', 'declined'],
        'selected' => ['selected', 'offer', 'accepted', 'declined'],
    ];

    /**
     * @param  list<string>  $jobIds
     * @return array<string, int> keyed by STAGES
     */
    public static function for(array $jobIds): array
    {
        $applications = fn () => Application::query()->whereIn('job_id', $jobIds);
        $reached = fn (array $statuses) => $applications()->where(fn (Builder $query) => $query
            ->whereIn('status', $statuses)
            ->orWhereHas('stageEvents', fn (Builder $events) => $events->whereIn('to_status', $statuses)))->count();

        return [
            'applied' => $applications()->count(),
            'qualified' => $reached(self::REACHED['qualified']),
            'interview' => $reached(self::REACHED['interview']),
            'selected' => $reached(self::REACHED['selected']),
            'mobility' => $applications()->whereHas('mobilityCase', fn (Builder $case) => $case->whereIn('status', [Open::$name, Completed::$name]))->count(),
            'joined' => self::joined($jobIds),
        ];
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
