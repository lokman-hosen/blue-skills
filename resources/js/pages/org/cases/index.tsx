import { Head, Link, router } from '@inertiajs/react';
import { CircleAlert, FileClock, Plane, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DataTable } from '@/components/bs/data-table';
import type { DataTableColumn } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterPill } from '@/components/bs/filter-pill';
import type { Paginated } from '@/components/bs/jobs/types';
import { CaseProgress } from '@/components/bs/mobility/case-progress';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type CaseRow = App.Domain.Mobility.Data.CaseListItemData;
type Filters = { state: 'open' | 'closed' | 'all'; attention: string | null; q: string };

type Props = {
    cases: Paginated<CaseRow>;
    counts: App.Domain.Mobility.Data.CaseCountsData;
    filters: Filters;
};

/** Every case the organization runs or follows (prototype a-cases, D-080). */
export default function OrgCases({ cases, counts, filters }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const [search, setSearch] = useState(filters.q);
    const query = (next: Partial<Filters>) => {
        const merged = { ...filters, ...next };

        return {
            query: {
                state: merged.state === 'open' ? undefined : merged.state,
                attention: merged.attention ?? undefined,
                q: merged.q || undefined,
            },
        };
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(org.cases.index.url(query({ q: search.trim() })), {}, { preserveScroll: true });
    };

    const attention = [
        {
            key: 'documents',
            label: t('mobility.list.documents_to_check'),
            count: counts.documentsToCheck,
        },
        { key: 'overdue', label: t('mobility.list.overdue'), count: counts.overdue },
        { key: 'on_hold', label: t('mobility.list.on_hold'), count: counts.onHold },
    ];

    const columns: DataTableColumn<CaseRow>[] = [
        {
            key: 'worker',
            header: t('mobility.worker'),
            primary: true,
            cell: (row) => (
                <Link
                    href={org.cases.show(row.id)}
                    className="font-semibold text-brand hover:underline"
                >
                    {row.workerName}
                    <span className="block font-mono text-xs font-normal text-muted" dir="ltr">
                        {row.reference}
                    </span>
                </Link>
            ),
        },
        {
            key: 'job',
            header: t('jobs.column.job'),
            cell: (row) => (
                <span>
                    {row.jobTitle}
                    <span className="block font-mono text-xs text-muted" dir="ltr">
                        {row.jobCode}
                    </span>
                </span>
            ),
        },
        {
            key: 'stage',
            header: t('mobility.current_stage'),
            cell: (row) => (
                <span className="flex flex-col items-start gap-1.5">
                    {row.stage ? (
                        <StatusChip
                            tone={row.onHold ? 'bad' : 'warn'}
                            label={
                                row.onHold
                                    ? t('mobility.list.stage_on_hold', { stage: row.stage })
                                    : row.stage
                            }
                        />
                    ) : (
                        <StatusChip status={row.status} />
                    )}
                    <CaseProgress
                        progress={row.progress}
                        label={t('mobility.list.progress', {
                            done: formatNumber(
                                row.progress.filter((s) => s === 'done' || s === 'skipped').length,
                            ),
                            total: formatNumber(row.progress.length),
                        })}
                    />
                </span>
            ),
        },
        {
            key: 'due',
            header: t('mobility.list.due'),
            cell: (row) =>
                row.dueOn ? (
                    <span className={row.overdue ? 'font-semibold text-bad' : undefined}>
                        {formatDate(row.dueOn)}
                        {row.overdue && (
                            <span className="block text-xs">
                                {t('mobility.list.overdue_short')}
                            </span>
                        )}
                    </span>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: 'documents',
            header: t('mobility.list.documents'),
            cell: (row) =>
                row.documentsToCheck > 0 ? (
                    <span className="inline-flex items-center gap-1 font-semibold text-stamp">
                        <FileClock aria-hidden className="size-4" />
                        {t('mobility.list.to_check', { count: formatNumber(row.documentsToCheck) })}
                    </span>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: 'managed',
            header: t('mobility.list.managed_by'),
            cell: (row) => (
                <span>
                    {row.managedBy}
                    {row.viewOnly && (
                        <span className="block text-xs text-muted">
                            {t('mobility.list.view_only')}
                        </span>
                    )}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title={t('nav.mobility')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.mobility')}
                    description={t('mobility.list.body', { count: formatNumber(counts.open) })}
                />

                {attention.some((item) => item.count > 0) && (
                    <div className="flex flex-wrap gap-2">
                        {attention
                            .filter((item) => item.count > 0)
                            .map((item) => (
                                <Link
                                    key={item.key}
                                    href={org.cases.index(
                                        query({ state: 'open', attention: item.key }),
                                    )}
                                    className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-warn/40 bg-warn-soft px-3 text-sm font-semibold hover:border-warn"
                                >
                                    <CircleAlert aria-hidden className="size-4 text-warn" />
                                    {item.label}
                                    <span className="tnum">{formatNumber(item.count)}</span>
                                </Link>
                            ))}
                    </div>
                )}

                <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <nav aria-label={t('mobility.list.states')} className="flex flex-wrap gap-1.5">
                        {(['open', 'closed', 'all'] as const).map((state) => (
                            <FilterPill
                                key={state}
                                href={org.cases.index(query({ state, attention: null }))}
                                active={filters.state === state && filters.attention === null}
                                label={t(`mobility.list.state.${state}`)}
                            />
                        ))}
                        {filters.attention && (
                            <FilterPill
                                href={org.cases.index(query({ attention: filters.attention }))}
                                active
                                label={t(`mobility.list.attention.${filters.attention}`)}
                            />
                        )}
                    </nav>
                    <form onSubmit={submit} role="search" className="flex items-end gap-2">
                        <div className="flex flex-col gap-1">
                            <Label htmlFor="case-search" className="sr-only">
                                {t('mobility.list.search')}
                            </Label>
                            <Input
                                id="case-search"
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={t('mobility.list.search')}
                                className="h-11 w-full md:w-64"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="outline"
                            className="min-h-11"
                            aria-label={t('mobility.list.search')}
                        >
                            <Search aria-hidden className="size-4" />
                        </Button>
                    </form>
                </div>

                <DataTable
                    caption={t('nav.mobility')}
                    columns={columns}
                    rows={cases.data}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={Plane}
                            title={
                                filters.state === 'open' && !filters.attention && !filters.q
                                    ? t('mobility.list.empty_title')
                                    : t('mobility.list.empty_filtered')
                            }
                            description={t('mobility.list.empty_body')}
                        />
                    }
                />
                <Pager page={cases} />
            </div>
        </>
    );
}
