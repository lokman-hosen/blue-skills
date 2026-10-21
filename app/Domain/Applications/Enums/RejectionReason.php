<?php

namespace App\Domain\Applications\Enums;

/**
 * Why an organization ends an application. Shown to the worker with the stage history.
 */
enum RejectionReason: string
{
    case RequirementsNotMet = 'requirements_not_met';
    case ExperienceInsufficient = 'experience_insufficient';
    case SkillsNotShown = 'skills_not_shown';
    case InterviewNotPassed = 'interview_not_passed';
    case AssessmentNotPassed = 'assessment_not_passed';
    case PositionsFilled = 'positions_filled';
    case NotAvailable = 'not_available';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::RequirementsNotMet => 'Requirements not met',
            self::ExperienceInsufficient => 'Not enough experience',
            self::SkillsNotShown => 'Skills not shown',
            self::InterviewNotPassed => 'Interview not passed',
            self::AssessmentNotPassed => 'Trade assessment not passed',
            self::PositionsFilled => 'All positions filled',
            self::NotAvailable => 'Not available in time',
            self::Other => 'Other',
        };
    }
}
