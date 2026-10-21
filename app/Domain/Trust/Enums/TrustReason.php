<?php

namespace App\Domain\Trust\Enums;

/**
 * Reason codes for trust decisions (spec §10): why a report was dismissed, why something was
 * suspended or restored, how an appeal was decided. Kept on the record and in the audit log.
 */
enum TrustReason: string
{
    // Action taken
    case FakeJob = 'fake_job';
    case UnauthorizedPayment = 'unauthorized_payment';
    case MisleadingConditions = 'misleading_conditions';
    case Impersonation = 'impersonation';
    case Harassment = 'harassment';
    case FakeCredentials = 'fake_credentials';
    case RepeatedBreaches = 'repeated_breaches';

    // No action / restore
    case NoEvidence = 'no_evidence';
    case NotABreach = 'not_a_breach';
    case Duplicate = 'duplicate';
    case Resolved = 'resolved';
    case AppealUpheld = 'appeal_upheld';
    case AppealOverturned = 'appeal_overturned';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::FakeJob => 'Fake or non-existent job',
            self::UnauthorizedPayment => 'Asked workers for money not allowed',
            self::MisleadingConditions => 'Misleading pay or conditions',
            self::Impersonation => 'Pretending to be someone else',
            self::Harassment => 'Harassment',
            self::FakeCredentials => 'Fake documents or credentials',
            self::RepeatedBreaches => 'Repeated breaches',
            self::NoEvidence => 'Not enough evidence',
            self::NotABreach => 'Not against the rules',
            self::Duplicate => 'Duplicate of another report',
            self::Resolved => 'Resolved with the parties',
            self::AppealUpheld => 'Appeal reviewed: suspension stays',
            self::AppealOverturned => 'Appeal accepted',
        };
    }

    /**
     * @return list<self>
     */
    public static function forActions(): array
    {
        return [self::FakeJob, self::UnauthorizedPayment, self::MisleadingConditions, self::Impersonation, self::Harassment, self::FakeCredentials, self::RepeatedBreaches];
    }

    /**
     * @return list<self>
     */
    public static function forDismissal(): array
    {
        return [self::NoEvidence, self::NotABreach, self::Duplicate, self::Resolved];
    }
}
