<?php

namespace App\Domain\Jobs\Enums;

/**
 * Experience the job asks for. "Other" carries the poster's own words in `experience_other`.
 * The level also sets `min_experience_years`, which "X of Y requirements met" reads (rule 12).
 */
enum ExperienceLevel: string
{
    case None = 'none';
    case UnderOne = 'under_1';
    case OneToThree = '1_3';
    case ThreeToFive = '3_5';
    case FiveToTen = '5_10';
    case TenPlus = '10_plus';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::UnderOne => 'Less than 1 year',
            self::OneToThree => '1–3 years',
            self::ThreeToFive => '3–5 years',
            self::FiveToTen => '5–10 years',
            self::TenPlus => '10+ years',
            self::Other => 'Other',
        };
    }

    /**
     * The minimum years matching checks. Null when no experience is needed or the level is the
     * poster's own words, so matching never invents a requirement.
     */
    public function minYears(): ?int
    {
        return match ($this) {
            self::OneToThree => 1,
            self::ThreeToFive => 3,
            self::FiveToTen => 5,
            self::TenPlus => 10,
            self::None, self::UnderOne, self::Other => null,
        };
    }

    /**
     * The level for a minimum stored before levels existed (data migration, D-117).
     */
    public static function fromYears(int $years): self
    {
        return match (true) {
            $years <= 0 => self::None,
            $years < 3 => self::OneToThree,
            $years < 5 => self::ThreeToFive,
            $years < 10 => self::FiveToTen,
            default => self::TenPlus,
        };
    }
}
