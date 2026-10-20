<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Models\WorkerExperience;
use Spatie\LaravelData\Data;

class WorkerExperienceData extends Data
{
    public function __construct(
        public string $id,
        public string $countryCode,
        public string $countryName,
        public string $role,
        public ?string $employerName,
        /** Stated years, or counted from the dates. */
        public ?int $years,
        public ?string $startedOn,
        public ?string $endedOn,
        public ?string $description,
    ) {}

    public static function fromExperience(WorkerExperience $experience): self
    {
        return new self(
            id: $experience->id,
            countryCode: $experience->country_code,
            countryName: $experience->country->name ?? $experience->country_code,
            role: $experience->role,
            employerName: $experience->employer_name,
            years: $experience->yearsWorked(),
            startedOn: $experience->started_on?->toDateString(),
            endedOn: $experience->ended_on?->toDateString(),
            description: $experience->description,
        );
    }
}
