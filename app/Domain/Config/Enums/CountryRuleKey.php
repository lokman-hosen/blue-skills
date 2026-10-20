<?php

namespace App\Domain\Config\Enums;

enum CountryRuleKey: string
{
    case Languages = 'languages';
    case AgencyEligibility = 'agency_eligibility';
    case WorkerCostPolicy = 'worker_cost_policy';
    case AllowedWorkerCostCategories = 'allowed_worker_cost_categories';
    case MandatoryDisclosures = 'mandatory_disclosures';
    case DocumentTypes = 'document_types';
    case MobilityStageTemplate = 'mobility_stage_template';
    case LocalNotice = 'local_notice';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Languages => 'Languages',
            self::AgencyEligibility => 'Agency eligibility',
            self::WorkerCostPolicy => 'Worker cost policy',
            self::AllowedWorkerCostCategories => 'Allowed worker cost categories',
            self::MandatoryDisclosures => 'Mandatory disclosures',
            self::DocumentTypes => 'Document types',
            self::MobilityStageTemplate => 'Mobility stage template',
            self::LocalNotice => 'Local notice',
        };
    }
}
