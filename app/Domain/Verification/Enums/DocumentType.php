<?php

namespace App\Domain\Verification\Enums;

enum DocumentType: string
{
    case Passport = 'passport';
    case NationalId = 'national_id';
    case Photo = 'photo';
    case Certificate = 'certificate';
    case EmployerReference = 'employer_reference';
    case TradeLicence = 'trade_licence';
    case AgencyLicence = 'agency_licence';
    case Accreditation = 'accreditation';
    case Contract = 'contract';
    case Medical = 'medical';
    case PoliceClearance = 'police_clearance';
    case WorkPermit = 'work_permit';
    case Visa = 'visa';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Passport => 'Passport',
            self::NationalId => 'National ID',
            self::Photo => 'Photo',
            self::Certificate => 'Certificate',
            self::EmployerReference => 'Employer reference',
            self::TradeLicence => 'Trade licence',
            self::AgencyLicence => 'Agency licence',
            self::Accreditation => 'Accreditation',
            self::Contract => 'Contract',
            self::Medical => 'Medical',
            self::PoliceClearance => 'Police clearance',
            self::WorkPermit => 'Work permit',
            self::Visa => 'Visa',
            self::Other => 'Other',
        };
    }
}
