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

    // Organization members
    case NewApplicants = 'new_applicants';
    case ApplicationAnswered = 'application_answered';
    case OrganizationMessage = 'organization_message';

    public function forWorkers(): bool
    {
        return in_array($this, self::workerEvents(), true);
    }

    /**
     * @return list<self>
     */
    public static function workerEvents(): array
    {
        return [self::Shortlisted, self::InterviewScheduled, self::OfferReceived, self::ApplicationRejected, self::JobChanged, self::JobInvitation, self::WorkerMessage];
    }

    /**
     * @return list<self>
     */
    public static function organizationEvents(): array
    {
        return [self::NewApplicants, self::ApplicationAnswered, self::OrganizationMessage];
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
            self::NewApplicants => 'New applicants (daily)',
            self::ApplicationAnswered => 'Worker accepted, declined or withdrew',
            self::OrganizationMessage => 'New message from a worker',
        };
    }
}
