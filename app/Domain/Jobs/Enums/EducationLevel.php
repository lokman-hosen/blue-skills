<?php

namespace App\Domain\Jobs\Enums;

/**
 * Minimum education. "Other" carries the poster's own words in `education_other`.
 */
enum EducationLevel: string
{
    case None = 'none';
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Diploma = 'diploma';
    case Degree = 'degree';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'No formal requirement',
            self::Primary => 'Primary',
            self::Secondary => 'Secondary',
            self::Diploma => 'Diploma / Technical',
            self::Degree => 'Degree',
            self::Other => 'Other',
        };
    }
}
