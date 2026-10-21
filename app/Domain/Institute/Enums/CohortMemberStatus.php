<?php

namespace App\Domain\Institute\Enums;

/**
 * invited (roster line, not answered) → accepted | declined; accepted → left when the graduate
 * leaves the institute (product rule 11).
 */
enum CohortMemberStatus: string
{
    case Invited = 'invited';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Left = 'left';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Invited',
            self::Accepted => 'Joined',
            self::Declined => 'Declined',
            self::Left => 'Left',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Invited => 'warn',
            self::Accepted => 'ok',
            self::Declined, self::Left => 'neutral',
        };
    }
}
