<?php

namespace App\Support;

use NumberFormatter;

/**
 * Whole currency units ↔ minor units using each currency's own decimals (JPY 0, OMR 3).
 * Money is always stored as `amount_minor` + `currency` (CLAUDE.md).
 */
final class Money
{
    public static function decimals(string $currency): int
    {
        $formatter = new NumberFormatter("en@currency={$currency}", NumberFormatter::CURRENCY);

        return (int) $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS);
    }

    public static function toMinor(int $amount, string $currency): int
    {
        return $amount * 10 ** self::decimals($currency);
    }

    public static function toWhole(int $amountMinor, string $currency): int
    {
        return intdiv($amountMinor, 10 ** self::decimals($currency));
    }

    /**
     * A typed decimal amount ("1500", "1500.5", "1500.50") to minor units without floats.
     */
    public static function parseMinor(string $amount, string $currency): int
    {
        $decimals = self::decimals($currency);
        [$whole, $fraction] = array_pad(explode('.', trim($amount), 2), 2, '');
        $fraction = substr(str_pad($fraction, $decimals, '0'), 0, $decimals);

        return ((int) $whole) * 10 ** $decimals + ($decimals > 0 ? (int) $fraction : 0);
    }
}
