<?php

namespace App\Domain\Verification\Enums;

enum VerificationType: string
{
    case Organization = 'organization';
    case AgencyLicence = 'agency_licence';
    case InstituteAccreditation = 'institute_accreditation';
    case JobMandate = 'job_mandate';
    case Job = 'job';
    case WorkerEvidence = 'worker_evidence';
    case SkillShow = 'skill_show';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Organization => 'Organization',
            self::AgencyLicence => 'Agency licence',
            self::InstituteAccreditation => 'Institute accreditation',
            self::JobMandate => 'Job mandate',
            self::Job => 'Job',
            self::WorkerEvidence => 'Worker evidence',
            self::SkillShow => 'SkillShow',
        };
    }
}
