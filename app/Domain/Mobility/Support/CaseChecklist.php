<?php

namespace App\Domain\Mobility\Support;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Verification\Enums\DocumentType;

/**
 * The documents a case needs, from the destination's `document_types` country rule (D-079).
 * Who provides each one and whether the employer sees it by default follow from its type.
 */
final class CaseChecklist
{
    /** Destinations without a rule still ask for a passport copy and a signed contract. */
    public const GENERIC = [DocumentType::Passport, DocumentType::Contract];

    /** Issued by the organization side; everything else comes from the worker. */
    public const FROM_ORGANIZATION = [DocumentType::Contract, DocumentType::WorkPermit, DocumentType::Visa];

    /** Shared from the worker's Documents page rather than uploaded to the case alone. */
    public const IDENTITY = [DocumentType::Passport, DocumentType::NationalId];

    /**
     * @return list<DocumentType>
     */
    public static function for(string $countryCode): array
    {
        $values = CountryRule::query()
            ->where('country_code', $countryCode)
            ->where('key', CountryRuleKey::DocumentTypes)
            ->value('value');

        $types = is_array($values)
            ? array_values(array_filter(array_map(fn ($value) => is_string($value) ? DocumentType::tryFrom($value) : null, $values)))
            : [];

        return $types !== [] ? $types : self::GENERIC;
    }

    public static function provider(DocumentType $type): DocumentProvider
    {
        return in_array($type, self::FROM_ORGANIZATION, true) ? DocumentProvider::Organization : DocumentProvider::Worker;
    }

    /**
     * Identity scans stay with the managing organization unless the worker says otherwise
     * (prototype: passport copy "Agency only").
     */
    public static function employerCanViewByDefault(DocumentType $type): bool
    {
        return ! in_array($type, self::IDENTITY, true);
    }

    /**
     * Checklist titles, e.g. "Medical certificate" rather than "Medical".
     */
    public static function title(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::Passport => 'Passport copy',
            DocumentType::Photo => 'Passport-size photo',
            DocumentType::Medical => 'Medical certificate',
            DocumentType::Contract => 'Signed contract',
            DocumentType::Certificate => 'Attested certificates',
            default => $type->label(),
        };
    }
}
