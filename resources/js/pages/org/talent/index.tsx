import { Head, Link, router } from '@inertiajs/react';
import { Lock, Search, SearchX } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterSheet } from '@/components/bs/filter-sheet';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { TalentCard } from '@/components/bs/talent-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';
import organizations from '@/routes/organizations';

type Filters = {
    q: string | null;
    occupation: string | null;
    min_years: number | null;
    country: string | null;
    worked_in: string | null;
    passport_ready: boolean;
    available_soon: boolean;
    language: string | null;
    verified_evidence: boolean;
    skillshow: boolean;
    job: string | null;
};

type Props = {
    verified: boolean;
    results: Paginated<App.Domain.Applications.Data.TalentCardData> | null;
    filters: Filters;
    options: {
        occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
        countries: App.Domain.Config.Data.CountryOptionData[];
        languages: { code: string; name: string }[];
        jobs: { code: string; title: string }[];
    };
};

const TOGGLES = ['passport_ready', 'available_soon', 'verified_evidence', 'skillshow'] as const;

const CLEARED: Filters = {
    q: null,
    occupation: null,
    min_years: null,
    country: null,
    worked_in: null,
    passport_ready: false,
    available_soon: false,
    language: null,
    verified_evidence: false,
    skillshow: false,
    job: null,
};

/**
 * Talent search (spec §8, deck slide 17): structured filters over workers who chose to be
 * found. Results are not ranked by a score or by video; choosing a job adds "X of Y requirements
 * met" to each card.
 */
export default function TalentIndex({ verified, results, filters, options }: Props) {
    const { t, formatNumber } = useTranslation();
    const [draft, setDraft] = useState<Filters>(filters);

    const apply = (next: Filters = draft) => {
        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([, value]) => value !== null && value !== '' && value !== false,
            ),
        );
        router.get(org.talent.index.url(), query as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        apply();
    };

    const activeCount = [
        draft.occupation,
        draft.min_years,
        draft.country,
        draft.worked_in,
        draft.language,
        draft.job,
        ...TOGGLES.map((key) => draft[key]),
    ].filter(Boolean).length;

    const select = (
        id: string,
        label: string,
        value: string | null,
        anyLabel: string,
        items: { value: string; label: string }[],
        onChange: (value: string | null) => void,
    ) => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <NativeSelect
                id={id}
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value || null)}
            >
                <option value="">{anyLabel}</option>
                {items.map((item) => (
                    <option key={item.value} value={item.value}>
                        {item.label}
                    </option>
                ))}
            </NativeSelect>
        </div>
    );

    if (!verified) {
        return (
            <>
                <Head title={t('nav.talent')} />
                <div className="flex flex-col gap-5">
                    <PageHeader title={t('nav.talent')} description={t('talent.body')} />
                    <EmptyState
                        icon={Lock}
                        title={t('talent.locked_title')}
                        description={t('talent.locked_body')}
                        action={
                            <Button asChild className="min-h-11">
                                <Link href={organizations.verification.edit()}>
                                    {t('talent.locked_action')}
                                </Link>
                            </Button>
                        }
                    />
                </div>
            </>
        );
    }

    const countryItems = options.countries.map((country) => ({
        value: country.code,
        label: country.name,
    }));

    return (
        <>
            <Head title={t('nav.talent')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.talent')} description={t('talent.body')} />

                <form onSubmit={submit} role="search" className="flex gap-2">
                    <Label htmlFor="talent-search" className="sr-only">
                        {t('talent.placeholder')}
                    </Label>
                    <div className="relative min-w-0 flex-1">
                        <Search
                            aria-hidden
                            className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted"
                        />
                        <Input
                            id="talent-search"
                            type="search"
                            value={draft.q ?? ''}
                            maxLength={100}
                            onChange={(event) => setDraft({ ...draft, q: event.target.value })}
                            placeholder={t('talent.placeholder')}
                            className="h-11 ps-9 text-base"
                        />
                    </div>
                    <Button type="submit" className="min-h-11">
                        {t('jobs.search.submit')}
                    </Button>
                </form>

                <div className="grid gap-5 md:grid-cols-[250px_minmax(0,1fr)] md:items-start">
                    <FilterSheet
                        activeCount={activeCount}
                        onApply={() => apply()}
                        onClear={() => {
                            setDraft(CLEARED);
                            apply(CLEARED);
                        }}
                    >
                        {select(
                            'talent-job',
                            t('talent.filter.job'),
                            draft.job,
                            t('talent.filter.no_job'),
                            options.jobs.map((job) => ({
                                value: job.code,
                                label: `${job.title} · ${job.code}`,
                            })),
                            (job) => setDraft({ ...draft, job }),
                        )}
                        {select(
                            'talent-occupation',
                            t('talent.field.trade'),
                            draft.occupation,
                            t('jobs.search.any_trade'),
                            options.occupations.map((occupation) => ({
                                value: occupation.slug,
                                label: occupation.name,
                            })),
                            (occupation) => setDraft({ ...draft, occupation }),
                        )}
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="talent-years">{t('talent.filter.min_years')}</Label>
                            <Input
                                id="talent-years"
                                type="number"
                                inputMode="numeric"
                                min={0}
                                max={40}
                                className="h-11"
                                value={draft.min_years ?? ''}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        min_years:
                                            event.target.value === ''
                                                ? null
                                                : Number(event.target.value),
                                    })
                                }
                            />
                        </div>
                        {select(
                            'talent-country',
                            t('talent.field.lives_in'),
                            draft.country,
                            t('jobs.search.any_country'),
                            countryItems,
                            (country) => setDraft({ ...draft, country }),
                        )}
                        {select(
                            'talent-worked',
                            t('passport.countries'),
                            draft.worked_in,
                            t('jobs.search.any_country'),
                            countryItems,
                            (worked_in) => setDraft({ ...draft, worked_in }),
                        )}
                        {select(
                            'talent-language',
                            t('talent.field.languages'),
                            draft.language,
                            t('talent.filter.any_language'),
                            options.languages.map((language) => ({
                                value: language.code,
                                label: language.name,
                            })),
                            (language) => setDraft({ ...draft, language }),
                        )}
                        <fieldset className="flex flex-col gap-1">
                            <legend className="mb-1 text-sm font-medium">
                                {t('talent.filter.show_only')}
                            </legend>
                            {TOGGLES.map((key) => (
                                <label
                                    key={key}
                                    className="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={draft[key]}
                                        onChange={(event) =>
                                            setDraft({ ...draft, [key]: event.target.checked })
                                        }
                                        className="size-4 accent-[var(--color-brand)]"
                                    />
                                    {t(`talent.filter.${key}`)}
                                </label>
                            ))}
                        </fieldset>
                        <p className="text-xs text-muted">{t('talent.privacy_note')}</p>
                    </FilterSheet>

                    <section aria-live="polite" className="flex min-w-0 flex-col gap-3">
                        {results && (
                            <>
                                <p className="text-sm text-muted">
                                    {t('talent.count', { count: formatNumber(results.total) })}
                                </p>
                                {results.data.length === 0 ? (
                                    <EmptyState
                                        icon={SearchX}
                                        title={t('talent.empty_title')}
                                        description={t('talent.empty_body')}
                                    />
                                ) : (
                                    <ul className="flex flex-col gap-3">
                                        {results.data.map((worker) => (
                                            <li key={worker.reference}>
                                                <TalentCard worker={worker} />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <Pager page={results} />
                            </>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
