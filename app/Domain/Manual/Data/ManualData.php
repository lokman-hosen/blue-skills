<?php

namespace App\Domain\Manual\Data;

use Spatie\LaravelData\Data;

class ManualData extends Data
{
    /**
     * @param  list<ManualSectionData>  $sections
     */
    public function __construct(
        public string $role,
        public string $label,
        /** `worker`, `employer`, `agency`, `institute` or `staff`; translated via `manual.group.*`. */
        public string $group,
        public string $title,
        public string $introHtml,
        public array $sections,
        /** The manual isn't written in the current language yet, so it shows in English. */
        public bool $translationPending,
    ) {}
}
