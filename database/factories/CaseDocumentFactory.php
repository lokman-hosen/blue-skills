<?php

namespace Database\Factories;

use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Verification\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseDocument>
 */
class CaseDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobility_case_id' => MobilityCase::factory(),
            'type' => DocumentType::Medical,
            'title' => 'Medical certificate',
            'status' => CaseDocumentStatus::Requested,
            'provided_by' => DocumentProvider::Worker,
        ];
    }
}
