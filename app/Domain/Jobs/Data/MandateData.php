<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Verification\Data\ReviewDecisionData;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Worker\Support\MediaUrls;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A mandate as the agency, the employer or staff see it.
 */
class MandateData extends Data
{
    /**
     * @param  list<string>  $occupations
     * @param  list<array{name: string, url: ?string}>  $evidence
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $agency,
        public ?string $agencyLicence,
        public string $employer,
        public StatusData $status,
        public bool $usable,
        public array $occupations,
        public ?string $destinationCountryCode,
        public int $headcount,
        public ?string $validFrom,
        public ?string $validUntil,
        public ?string $employerNote,
        public ?string $employerRespondedAt,
        public int $jobs,
        public array $evidence,
        public ?ReviewDecisionData $lastDecision,
        public ?string $createdAt,
    ) {}

    public static function fromMandate(JobMandate $mandate, bool $withEvidence = false): self
    {
        $mandate->loadMissing(['agency', 'employer']);
        $review = $mandate->verificationRequests()->latest()->first();

        return new self(
            id: $mandate->id,
            reference: $mandate->reference,
            agency: $mandate->agency->display_name,
            agencyLicence: $mandate->agency->licence_number,
            employer: $mandate->employer->display_name,
            status: StatusData::fromEnum($mandate->status, $mandate->status->tone()),
            usable: $mandate->isUsable(),
            occupations: Occupation::query()->whereIn('id', $mandate->occupation_ids ?? [])->get()
                ->map(fn (Occupation $occupation) => $occupation->translatedName())->values()->all(),
            destinationCountryCode: $mandate->destination_country_code,
            headcount: $mandate->headcount,
            validFrom: $mandate->valid_from?->toDateString(),
            validUntil: $mandate->valid_until?->toDateString(),
            employerNote: $mandate->employer_note,
            employerRespondedAt: $mandate->employer_responded_at?->toIso8601String(),
            jobs: $mandate->jobs()->count(),
            evidence: $withEvidence && $review instanceof VerificationRequest
                ? $review->getMedia(VerificationRequest::EVIDENCE_COLLECTION)
                    ->map(fn (Media $media) => ['name' => $media->name, 'url' => MediaUrls::signed($media)])->values()->all()
                : [],
            lastDecision: $review instanceof VerificationRequest && $review->decision !== null ? ReviewDecisionData::fromRequest($review) : null,
            createdAt: $mandate->created_at?->toIso8601String(),
        );
    }
}
