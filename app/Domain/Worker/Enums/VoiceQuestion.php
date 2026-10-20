<?php

namespace App\Domain\Worker\Enums;

enum VoiceQuestion: string
{
    case Occupation = 'occupation';
    case Experience = 'experience';
    case Countries = 'countries';
    case Skills = 'skills';
    case Passport = 'passport';
    case Availability = 'availability';
    case Destinations = 'destinations';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Occupation => 'Occupation',
            self::Experience => 'Experience',
            self::Countries => 'Countries',
            self::Skills => 'Skills',
            self::Passport => 'Passport',
            self::Availability => 'Availability',
            self::Destinations => 'Destinations',
        };
    }
}
