<?php

namespace App\Domain\Institute\Enums;

enum AssessmentResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return $this === self::Pass ? 'Passed' : 'Not passed';
    }

    public function tone(): string
    {
        return $this === self::Pass ? 'ok' : 'bad';
    }
}
