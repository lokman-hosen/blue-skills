import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

export type Replacements = Record<string, string | number>;
export type Translate = (key: string, replacements?: Replacements, fallback?: string) => string;

/**
 * Replace Laravel-style `:name` placeholders. Longer names are replaced first so
 * `:count` never eats part of `:counter`.
 */
export function interpolate(text: string, replacements: Replacements = {}): string {
    return Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .reduce((result, name) => result.replaceAll(`:${name}`, String(replacements[name])), text);
}

/**
 * Look up a key in the shared translations (current locale merged over English).
 * Unknown keys return `fallback`, or the key itself so missing strings are easy to spot.
 */
export function translate(
    translations: Record<string, string>,
    key: string,
    replacements?: Replacements,
    fallback?: string,
): string {
    return interpolate(translations[key] ?? fallback ?? key, replacements);
}

export function useTranslation() {
    const { translations, locale } = usePage().props;

    const t: Translate = useCallback(
        (key, replacements, fallback) => translate(translations, key, replacements, fallback),
        [translations],
    );

    const format = useMemo(() => formatters(locale.code), [locale.code]);

    return { t, locale: locale.code, dir: locale.dir, ...format };
}

/** Intl-backed formatters. Numbers use Latin digits so amounts read the same everywhere. */
export function formatters(locale: string) {
    const tag = `${locale}-u-nu-latn`;

    return {
        formatMoney(money: App.Support.Data.MoneyData): string {
            const fractionDigits =
                new Intl.NumberFormat('en', {
                    style: 'currency',
                    currency: money.currency,
                }).resolvedOptions().maximumFractionDigits ?? 2;

            return new Intl.NumberFormat(tag, {
                style: 'currency',
                currency: money.currency,
                currencyDisplay: 'code',
                maximumFractionDigits: fractionDigits,
                minimumFractionDigits: 0,
            }).format(money.amountMinor / 10 ** fractionDigits);
        },
        formatNumber(value: number): string {
            return new Intl.NumberFormat(tag).format(value);
        },
        formatDate(
            isoDate: string,
            options: Intl.DateTimeFormatOptions = { dateStyle: 'medium' },
        ): string {
            // Date-only strings are calendar dates; render them in UTC so they never shift a day.
            const date = new Date(isoDate.length === 10 ? `${isoDate}T00:00:00Z` : isoDate);

            return new Intl.DateTimeFormat(tag, {
                timeZone: isoDate.length === 10 ? 'UTC' : undefined,
                ...options,
            }).format(date);
        },
    };
}
