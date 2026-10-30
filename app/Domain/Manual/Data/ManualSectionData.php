<?php

namespace App\Domain\Manual\Data;

use Spatie\LaravelData\Data;

class ManualSectionData extends Data
{
    public function __construct(
        /** Anchor for the table of contents. */
        public string $id,
        public string $title,
        /** Rendered from Markdown with raw HTML stripped. */
        public string $html,
    ) {}
}
