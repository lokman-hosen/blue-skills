<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\Support\HiringStages;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class RecruitmentProjectListItemData extends Data
{
    /**
     * @param  list<string>  $sourceCountryCodes
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $name,
        /** Translated via `status.project_{value}`. */
        public StatusData $status,
        public int $targetHeadcount,
        public int $joined,
        public int $jobs,
        public ?string $destinationCountryCode,
        public array $sourceCountryCodes,
        public ?string $targetDate,
    ) {}

    public static function fromProject(RecruitmentProject $project): self
    {
        /** @var string $status */
        $status = $project->status->getValue();
        /** @var list<string> $jobIds */
        $jobIds = $project->jobs()->pluck('id')->all();

        return new self(
            id: $project->id,
            reference: $project->reference,
            name: $project->name,
            status: new StatusData('project_'.$status, $project->status->label(), $project->status->tone()),
            targetHeadcount: $project->target_headcount,
            joined: HiringStages::joined($jobIds),
            jobs: count($jobIds),
            destinationCountryCode: $project->destination_country_code,
            sourceCountryCodes: $project->source_country_codes,
            targetDate: $project->target_date?->toDateString(),
        );
    }
}
