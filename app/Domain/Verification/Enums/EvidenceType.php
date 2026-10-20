<?php

namespace App\Domain\Verification\Enums;

enum EvidenceType: string
{
    case Identity = 'identity';
    case Passport = 'passport';
    case EmployerReference = 'employer_reference';
    case Certificate = 'certificate';
    case Training = 'training';
    case TradeAssessment = 'trade_assessment';
    case WorkExperience = 'work_experience';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Identity => 'Identity',
            self::Passport => 'Passport',
            self::EmployerReference => 'Employer reference',
            self::Certificate => 'Certificate',
            self::Training => 'Training',
            self::TradeAssessment => 'Trade assessment',
            self::WorkExperience => 'Work experience',
        };
    }
}
