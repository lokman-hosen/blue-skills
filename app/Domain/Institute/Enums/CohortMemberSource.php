<?php

namespace App\Domain\Institute\Enums;

/**
 * How a cohort member came in: a roster line the institute typed, or the invite link.
 */
enum CohortMemberSource: string
{
    case Roster = 'roster';
    case Link = 'link';
}
