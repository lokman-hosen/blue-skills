<?php

namespace App\Domain\Applications\Enums;

enum ApplicationConsentItem: string
{
    case SkillPassport = 'skill_passport';
    case SkillShow = 'skill_show';
    case WorkExperience = 'work_experience';
    case TrainingCertificates = 'training_certificates';
    case PassportScan = 'passport_scan';
    case PhoneNumber = 'phone_number';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::SkillPassport => 'Skill passport',
            self::SkillShow => 'SkillShow',
            self::WorkExperience => 'Work experience',
            self::TrainingCertificates => 'Training certificates',
            self::PassportScan => 'Passport scan',
            self::PhoneNumber => 'Phone number',
        };
    }
}
