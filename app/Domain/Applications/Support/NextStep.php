<?php

namespace App\Domain\Applications\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;

/**
 * What the worker should expect or do next, as a translation key under `applications.next.`
 */
final class NextStep
{
    public static function for(Application $application): string
    {
        $status = (string) $application->status->getValue();

        if ($status === 'interview') {
            $upcoming = $application->interviews->first(fn (Interview $interview) => $interview->isUpcoming());

            return $upcoming !== null ? 'attend_interview' : 'wait_interview';
        }

        return match ($status) {
            'applied' => 'wait_review',
            'reviewed', 'qualified' => 'being_considered',
            'shortlisted' => 'wait_interview',
            'trade_assessment' => 'assessment',
            'selected' => 'wait_offer',
            'offer' => 'answer_offer',
            'accepted' => 'mobility',
            default => 'closed',
        };
    }
}
