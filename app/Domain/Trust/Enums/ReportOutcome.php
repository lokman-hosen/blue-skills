<?php

namespace App\Domain\Trust\Enums;

/**
 * The only thing a reporter is told (D-088).
 */
enum ReportOutcome: string
{
    case ActionTaken = 'action_taken';
    case NoAction = 'no_action';
}
