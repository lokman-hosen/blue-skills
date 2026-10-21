<?php

namespace App\Domain\Messaging\Enums;

/**
 * Everything BLUESKILLS alerts people about (spec §9). Each event has an audience and the
 * channels it uses by default; people switch channels off per event in their preferences.
 */
enum NotificationEvent: string
{
    // Workers
    case Shortlisted = 'shortlisted';
    case InterviewScheduled = 'interview_scheduled';
    case OfferReceived = 'offer_received';
    case ApplicationRejected = 'application_rejected';
    case JobChanged = 'job_changed';
    case JobInvitation = 'job_invitation';
    case WorkerMessage = 'worker_message';
    case CaseOpened = 'case_opened';
    case CaseUpdated = 'case_updated';
    case CaseClosed = 'case_closed';
    case CaseDocument = 'case_document';
    case InstituteRecord = 'institute_record';
    case EvidenceReviewed = 'evidence_reviewed';
    case JobSuspended = 'job_suspended';
    case AccountSuspended = 'account_suspended';
    case ReportOutcome = 'report_outcome';
    case AppealDecided = 'appeal_decided';

    // Organization members
    case NewApplicants = 'new_applicants';
    case ApplicationAnswered = 'application_answered';
    case OrganizationMessage = 'organization_message';
    case MandateRequested = 'mandate_requested';
    case MandateAnswered = 'mandate_answered';
    case MandateReviewed = 'mandate_reviewed';
    case CaseDocumentAnswered = 'case_document_answered';
    case JobPausedMandate = 'job_paused_mandate';
    case GraduateJoined = 'graduate_joined';
    case OrganizationSuspended = 'organization_suspended';

    public function forWorkers(): bool
    {
        return in_array($this, self::workerEvents(), true);
    }

    /**
     * @return list<self>
     */
    public static function workerEvents(): array
    {
        return [
            self::Shortlisted, self::InterviewScheduled, self::OfferReceived, self::ApplicationRejected, self::JobChanged, self::JobInvitation, self::WorkerMessage,
            self::CaseOpened, self::CaseUpdated, self::CaseClosed, self::CaseDocument, self::InstituteRecord, self::EvidenceReviewed,
            self::JobSuspended, self::AccountSuspended, self::ReportOutcome, self::AppealDecided,
        ];
    }

    /**
     * @return list<self>
     */
    public static function organizationEvents(): array
    {
        return [
            self::NewApplicants, self::ApplicationAnswered, self::OrganizationMessage,
            self::MandateRequested, self::MandateAnswered, self::MandateReviewed, self::CaseDocumentAnswered, self::JobPausedMandate, self::GraduateJoined,
            self::OrganizationSuspended,
        ];
    }

    /**
     * Channels outside the app this event can use. Workers get WhatsApp (SMS as fallback);
     * organization members get email.
     *
     * @return list<DeliveryChannel>
     */
    public function channels(): array
    {
        return $this->forWorkers() ? [DeliveryChannel::WhatsApp, DeliveryChannel::Sms] : [DeliveryChannel::Mail];
    }

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Shortlisted => 'You were shortlisted',
            self::InterviewScheduled => 'Interview booked or changed',
            self::OfferReceived => 'Offer received',
            self::ApplicationRejected => 'Application closed by the employer',
            self::JobChanged => 'A job you applied to changed',
            self::JobInvitation => 'Invitation to apply',
            self::WorkerMessage => 'New message',
            self::CaseOpened => 'Your mobility case opened',
            self::CaseUpdated => 'Your mobility case moved on',
            self::CaseClosed => 'Your mobility case closed',
            self::CaseDocument => 'A case document needs you',
            self::InstituteRecord => 'An institute attested your training or recorded a test',
            self::EvidenceReviewed => 'BLUESKILLS checked a document you uploaded',
            self::JobSuspended => 'A job you applied to was suspended',
            self::AccountSuspended => 'Your account was suspended or restored',
            self::ReportOutcome => 'The outcome of something you reported',
            self::AppealDecided => 'A decision on your appeal',
            self::NewApplicants => 'New applicants (daily)',
            self::ApplicationAnswered => 'Worker accepted, declined or withdrew',
            self::OrganizationMessage => 'New message from a worker',
            self::MandateRequested => 'An agency asks you to confirm a mandate',
            self::MandateAnswered => 'The employer answered your mandate request',
            self::MandateReviewed => 'BLUESKILLS reviewed your mandate',
            self::CaseDocumentAnswered => 'A worker shared or answered a case document',
            self::JobPausedMandate => 'A job paused because its mandate lapsed',
            self::GraduateJoined => 'A graduate joined a cohort',
            self::OrganizationSuspended => 'Your organization or a job was suspended or restored',
        };
    }
}
