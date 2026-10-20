<?php

namespace App\Domain\Jobs\Enums;

enum JobCostCategory: string
{
    case RecruitmentFee = 'recruitment_fee';
    case Visa = 'visa';
    case WorkPermit = 'work_permit';
    case Medical = 'medical';
    case SkillsTest = 'skills_test';
    case LanguageTest = 'language_test';
    case Airfare = 'airfare';
    case DocumentAttestation = 'document_attestation';
    case Training = 'training';
    case Accommodation = 'accommodation';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::RecruitmentFee => 'Recruitment fee',
            self::Visa => 'Visa',
            self::WorkPermit => 'Work permit',
            self::Medical => 'Medical',
            self::SkillsTest => 'Skills test',
            self::LanguageTest => 'Language test',
            self::Airfare => 'Airfare',
            self::DocumentAttestation => 'Document attestation',
            self::Training => 'Training',
            self::Accommodation => 'Accommodation',
            self::Other => 'Other',
        };
    }
}
