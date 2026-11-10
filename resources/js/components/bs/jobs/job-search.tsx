import { Head, Link, router } from '@inertiajs/react';
import { Search, SearchX } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterSheet } from '@/components/bs/filter-sheet';
import { JobListingCard } from '@/components/bs/jobs/job-listing-card';
import type { JobFilters, Paginated } from '@/components/bs/jobs/types';
import { JOB_OPTIONS } from '@/components/bs/jobs/wizard-fields';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/lib/i18n';
import jobs from '@/routes/jobs';

export type JobSearchProps = {
    jobs: Paginated<App.Domain.Jobs.Data.JobListingData>;
    filters: JobFilters;
    countries: App.Domain.Config.Data.CountryOptionData[];
    occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
    industries: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
};

const TOGGLES = ['no_worker_costs', 'accommodation', 'salary_disclosed'] as const;

/** Find Jobs: search and filters over published jobs, newest first (spec §8). */
export function JobSearch({
    jobs: page,
    filters,
    countries,
    occupations,
    industries,
}: JobSearchProps) {
    const { t, formatNumber } = useTranslation();
    const [draft, setDraft] = useState<JobFilters>(filters);
    // Phones keep sorting in the filter sheet; from `md` it sits above the results.
    const isMobile = useIsMobile();

    const apply = (next: JobFilters = draft) => {
        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([key, value]) =>
                    value !== null &&
                    value !== '' &&
                    value !== false &&
                    !(key === 'sort' && value === 'newest'),
            ),
        );
        router.get(jobs.index.url(), query as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        apply();
    };

    const cleared: JobFilters = {
        q: null,
        country: null,
        occupation: null,
        industry: null,
        employment_type: null,
        no_worker_costs: false,
        accommodation: false,
        salary_disclosed: false,
        sort: 'newest',
    };
    const activeCount = [
        draft.country,
        draft.occupation,
        draft.industry,
        draft.employment_type,
        ...TOGGLES.map((key) => draft[key]),
    ].filter(Boolean).length;

    return (
        <>
            <Head title={t('jobs.search.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('jobs.search.title')} description={t('jobs.search.body')} />
                <form onSubmit={submit} role="search" className="flex gap-2">
                    <Label htmlFor="job-search" className="sr-only">
                        {t('jobs.search.placeholder')}
                    </Label>
                    <div className="relative min-w-0 flex-1">
                        <Search
                            aria-hidden
                            className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted"
                        />
                        <Input
                            id="job-search"
                            type="search"
                            value={draft.q ?? ''}
                            maxLength={100}
                            onChange={(event) => setDraft({ ...draft, q: event.target.value })}
                            placeholder={t('jobs.search.placeholder')}
                            className="h-11 ps-9 text-base"
                        />
                    </div>
                    <Button type="submit" className="min-h-11">
                        {t('jobs.search.submit')}
                    </Button>
                </form>

                <div className="grid gap-5 md:grid-cols-[240px_minmax(0,1fr)] md:items-start lg:grid-cols-[280px_minmax(0,1fr)] 2xl:grid-cols-[300px_minmax(0,1fr)]">
                    <FilterSheet
                        className="md:sticky md:top-20 md:max-h-[calc(100svh-6rem)] md:overflow-y-auto"
                        activeCount={activeCount}
                        onApply={() => apply()}
                        onClear={() => {
                            setDraft(cleared);
                            apply(cleared);
                        }}
                    >
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="filter-country">{t('jobs.field.destination')}</Label>
                            <NativeSelect
                                id="filter-country"
                                value={draft.country ?? ''}
                                onChange={(event) =>
                                    setDraft({ ...draft, country: event.target.value || null })
                                }
                            >
                                <option value="">{t('jobs.search.any_country')}</option>
                                {countries.map((country) => (
                                    <option key={country.code} value={country.code}>
                                        {country.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="filter-industry">{t('jobs.field.industry')}</Label>
                            <NativeSelect
                                id="filter-industry"
                                value={draft.industry ?? ''}
                                onChange={(event) =>
                                    setDraft({ ...draft, industry: event.target.value || null })
                                }
                            >
                                <option value="">{t('jobs.search.any_industry')}</option>
                                {industries.map((industry) => (
                                    <option key={industry.slug} value={industry.slug}>
                                        {industry.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="filter-occupation">{t('voice.label.occupation')}</Label>
                            <NativeSelect
                                id="filter-occupation"
                                value={draft.occupation ?? ''}
                                onChange={(event) =>
                                    setDraft({ ...draft, occupation: event.target.value || null })
                                }
                            >
                                <option value="">{t('jobs.search.any_trade')}</option>
                                {occupations.map((occupation) => (
                                    <option key={occupation.slug} value={occupation.slug}>
                                        {occupation.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="filter-employment-type">
                                {t('jobs.field.employment_type')}
                            </Label>
                            <NativeSelect
                                id="filter-employment-type"
                                value={draft.employment_type ?? ''}
                                onChange={(event) =>
                                    setDraft({
                                        ...draft,
                                        employment_type: (event.target.value ||
                                            null) as JobFilters['employment_type'],
                                    })
                                }
                            >
                                <option value="">{t('jobs.search.any_employment_type')}</option>
                                {JOB_OPTIONS.employment_type.map((type) => (
                                    <option key={type} value={type}>
                                        {t(`jobs.option.employment_type.${type}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <fieldset className="flex flex-col gap-1">
                            <legend className="mb-1 text-sm font-medium">
                                {t('jobs.search.conditions')}
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
                                    {t(`jobs.search.filter.${key}`)}
                                </label>
                            ))}
                        </fieldset>
                        {isMobile && (
                            <SortSelect
                                value={draft.sort}
                                onChange={(sort) => setDraft({ ...draft, sort })}
                            />
                        )}
                        <Button className="hidden min-h-11 md:inline-flex" onClick={() => apply()}>
                            {t('common.show_results')}
                        </Button>
                    </FilterSheet>

                    <section aria-live="polite" className="flex min-w-0 flex-col gap-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-muted">
                                {t('jobs.search.count', { count: formatNumber(page.total) })}
                            </p>
                            {!isMobile && (
                                <SortSelect
                                    inline
                                    value={draft.sort}
                                    onChange={(sort) => {
                                        const next = { ...draft, sort };
                                        setDraft(next);
                                        apply(next);
                                    }}
                                />
                            )}
                        </div>
                        {page.data.length === 0 ? (
                            <EmptyState
                                icon={SearchX}
                                title={t('jobs.search.empty_title')}
                                description={t('jobs.search.empty_body')}
                            />
                        ) : (
                            <ul className="grid gap-3 xl:grid-cols-2">
                                {page.data.map((job) => (
                                    <li key={job.code} className="flex">
                                        <JobListingCard job={job} />
                                    </li>
                                ))}
                            </ul>
                        )}
                        {page.last_page > 1 && (
                            <nav
                                aria-label={t('jobs.search.pages')}
                                className="flex items-center justify-between gap-2"
                            >
                                {page.prev_page_url ? (
                                    <Link
                                        href={page.prev_page_url}
                                        preserveScroll
                                        className="inline-flex min-h-11 items-center font-semibold text-brand"
                                    >
                                        {t('jobs.search.previous')}
                                    </Link>
                                ) : (
                                    <span />
                                )}
                                <span className="text-sm text-muted">
                                    {t('jobs.search.page', {
                                        page: formatNumber(page.current_page),
                                        last: formatNumber(page.last_page),
                                    })}
                                </span>
                                {page.next_page_url ? (
                                    <Link
                                        href={page.next_page_url}
                                        preserveScroll
                                        className="inline-flex min-h-11 items-center font-semibold text-brand"
                                    >
                                        {t('jobs.search.next')}
                                    </Link>
                                ) : (
                                    <span />
                                )}
                            </nav>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

/** Newest first or closing soonest: in the filter sheet on phones, above the results from `md`. */
function SortSelect({
    value,
    onChange,
    inline = false,
}: {
    value: JobFilters['sort'];
    onChange: (sort: JobFilters['sort']) => void;
    inline?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div className={inline ? 'flex items-center gap-2' : 'flex flex-col gap-1.5'}>
            <Label htmlFor="filter-sort" className={inline ? 'shrink-0 text-muted' : undefined}>
                {t('jobs.search.sort')}
            </Label>
            <NativeSelect
                id="filter-sort"
                value={value}
                onChange={(event) => onChange(event.target.value as JobFilters['sort'])}
            >
                <option value="newest">{t('jobs.search.sort_newest')}</option>
                <option value="deadline">{t('jobs.search.sort_deadline')}</option>
            </NativeSelect>
        </div>
    );
}
