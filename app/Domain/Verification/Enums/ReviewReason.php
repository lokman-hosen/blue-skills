<?php

namespace App\Domain\Verification\Enums;

/**
 * Reason codes staff give when they ask for more information or reject (spec §10). The code
 * is stored on the verification request and shown to the organization with how to fix it.
 */
enum ReviewReason: string
{
    // Organization registration
    case DocumentUnreadable = 'document_unreadable';
    case DocumentExpired = 'document_expired';
    case DetailsMismatch = 'details_mismatch';
    case NotARegisteredBusiness = 'not_a_registered_business';
    case LicenceOutOfScope = 'licence_out_of_scope';

    // Jobs
    case UnlawfulWorkerFee = 'unlawful_worker_fee';
    case ConditionsUnclear = 'conditions_unclear';
    case SalaryImplausible = 'salary_implausible';
    case DuplicateJob = 'duplicate_job';
    case ProhibitedContent = 'prohibited_content';

    // Both
    case MoreInformationNeeded = 'more_information_needed';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::DocumentUnreadable => 'Document unreadable',
            self::DocumentExpired => 'Document expired',
            self::DetailsMismatch => 'Details do not match the document',
            self::NotARegisteredBusiness => 'Not a registered business',
            self::LicenceOutOfScope => 'Licence does not cover recruitment',
            self::UnlawfulWorkerFee => 'Worker fee not allowed on this corridor',
            self::ConditionsUnclear => 'Work conditions unclear or incomplete',
            self::SalaryImplausible => 'Salary looks wrong',
            self::DuplicateJob => 'Duplicate of another job',
            self::ProhibitedContent => 'Prohibited or discriminatory content',
            self::MoreInformationNeeded => 'More information needed',
        };
    }

    /**
     * @return list<self>
     */
    public static function forOrganizations(): array
    {
        return [
            self::DocumentUnreadable, self::DocumentExpired, self::DetailsMismatch,
            self::NotARegisteredBusiness, self::LicenceOutOfScope, self::MoreInformationNeeded,
        ];
    }

    /**
     * @return list<self>
     */
    public static function forJobs(): array
    {
        return [
            self::UnlawfulWorkerFee, self::ConditionsUnclear, self::SalaryImplausible,
            self::DuplicateJob, self::ProhibitedContent, self::MoreInformationNeeded,
        ];
    }
}
