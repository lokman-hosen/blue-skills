<?php

namespace App\Domain\Trust\Enums;

enum ReportCategory: string
{
    case FakeJob = 'fake_job';
    case UnauthorizedPayment = 'unauthorized_payment';
    case MisleadingSalary = 'misleading_salary';
    case Impersonation = 'impersonation';
    case Harassment = 'harassment';
    case FakeCredential = 'fake_credential';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::FakeJob => 'Fake job',
            self::UnauthorizedPayment => 'Unauthorized payment',
            self::MisleadingSalary => 'Misleading salary',
            self::Impersonation => 'Impersonation',
            self::Harassment => 'Harassment',
            self::FakeCredential => 'Fake credential',
            self::Other => 'Other',
        };
    }
}
