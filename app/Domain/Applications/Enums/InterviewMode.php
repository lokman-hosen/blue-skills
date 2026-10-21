<?php

namespace App\Domain\Applications\Enums;

enum InterviewMode: string
{
    case InPerson = 'in_person';
    case Video = 'video';
    case Phone = 'phone';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'In person',
            self::Video => 'Video call',
            self::Phone => 'Phone call',
        };
    }
}
