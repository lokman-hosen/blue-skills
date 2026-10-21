<?php

namespace App\Domain\Mobility\Enums;

enum CaseClosedReason: string
{
    case Joined = 'joined';
    case WorkerWithdrew = 'worker_withdrew';
    case EmployerCancelled = 'employer_cancelled';
    case VisaRefused = 'visa_refused';
    case MedicalUnfit = 'medical_unfit';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Joined => 'Joined',
            self::WorkerWithdrew => 'Worker withdrew',
            self::EmployerCancelled => 'Employer cancelled',
            self::VisaRefused => 'Visa refused',
            self::MedicalUnfit => 'Medical test not passed',
            self::Other => 'Other',
        };
    }
}
