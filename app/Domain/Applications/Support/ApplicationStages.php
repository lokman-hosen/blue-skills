<?php

namespace App\Domain\Applications\Support;

use App\Domain\Applications\Data\StageData;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Applications\States\Application\Declined;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Applications\States\Application\Withdrawn;

/**
 * The stage list shown on the application tracker (spec §6): done, current, skipped (the
 * organization moved past it) or pending, with the date each was reached. A rejected,
 * withdrawn or declined application stops at the stage it had reached, marked as blocked.
 */
final class ApplicationStages
{
    public const ORDER = [
        'applied', 'reviewed', 'qualified', 'shortlisted', 'interview', 'trade_assessment', 'selected', 'offer', 'accepted',
    ];

    /**
     * Stages a worker can be moved to by the organization, in order.
     */
    public const MOVABLE = ['reviewed', 'qualified', 'shortlisted', 'interview', 'trade_assessment', 'selected'];

    /**
     * @return list<StageData>
     */
    public static function for(Application $application): array
    {
        $events = $application->stageEvents;
        $reached = [];

        foreach ($events as $event) {
            /** @var ApplicationStageEvent $event */
            $reached[$event->to_status] = $event->created_at?->toIso8601String();
        }

        $status = (string) $application->status->getValue();
        $stopped = $application->status->equals(Rejected::class, Withdrawn::class, Declined::class);
        $current = $stopped ? self::lastReached($reached) : $status;
        $currentIndex = (int) array_search($current, self::ORDER, true);

        if ($application->status->equals(Declined::class)) {
            $currentIndex = (int) array_search('offer', self::ORDER, true);
        }

        $stages = [];

        foreach (self::ORDER as $index => $key) {
            $state = match (true) {
                $index < $currentIndex => array_key_exists($key, $reached) ? 'done' : 'skipped',
                $index === $currentIndex => $stopped ? 'blocked' : ($application->status->equals(Accepted::class) ? 'done' : 'current'),
                default => 'pending',
            };
            $stages[] = new StageData($key, $state, $reached[$key] ?? null);
        }

        return $stages;
    }

    /**
     * Pipeline stages the organization can move this applicant to now, in order.
     *
     * @return list<string>
     */
    public static function nextFor(Application $application): array
    {
        return array_values(array_filter(
            self::MOVABLE,
            fn (string $stage) => $application->status->canTransitionTo(ApplicationState::resolveStateClass($stage) ?? ''),
        ));
    }

    /**
     * @param  array<string, string|null>  $reached
     */
    private static function lastReached(array $reached): string
    {
        $last = 'applied';

        foreach (self::ORDER as $key) {
            if (array_key_exists($key, $reached)) {
                $last = $key;
            }
        }

        return $last;
    }
}
