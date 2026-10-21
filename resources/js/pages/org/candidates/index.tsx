import { Head, Link, router } from '@inertiajs/react';
import { Plane, Users } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/bs/data-table';
import type { DataTableColumn } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterSheet } from '@/components/bs/filter-sheet';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Candidate = App.Domain.Applications.Data.CandidateListItemData;
type Filters = { job: string | null; stage: string | null; q: string };

type Props = {
    candidates: Paginated<Candidate>;
    jobs: { code: string; title: string }[];
    stages: string[];
    filters: Filters;
};

/** Applicants across all of the organization's jobs (agency "Candidates", D-080). */
export default function Candidates({ candidates, jobs, stages, filters }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const [draft, setDraft] = useState<Filters>(filters);
    const active = [filters.job, filters.stage, filters.q].filter(Boolean).length;

    const apply = (next: Filters) =>
        router.get(
            org.candidates.index.url({
                query: {
                    job: next.job || undefined,
                    stage: next.stage || undefined,
                    q: next.q.trim() || undefined,
                },
            }),
            {},
            { preserveScroll: true },
        );

    const columns: DataTableColumn<Candidate>[] = [
        {
            key: 'name',
            header: t('applicants.name'),
            primary: true,
            cell: (row) => (
                <Link
                    href={org.applicants.show(row.applicant.id)}
                    className="font-semibold text-brand hover:underline"
                >
                    {row.applicant.name}
                    <span className="block text-xs font-normal text-muted">
                        {[row.applicant.occupation, row.applicant.currentCountry]
                            .filter(Boolean)
                            .join(' · ')}
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
            key: 'match',
            header: t('applicants.match'),
            cell: (row) => (
                <StatusChip
                    tone="brand"
                    icon={false}
                    label={t('jobs.match.summary', {
                        met: formatNumber(row.applicant.met),
                        total: formatNumber(row.applicant.total),
                    })}
                />
            ),
        },
        {
            key: 'stage',
            header: t('applicants.stage'),
            cell: (row) => (
                <span className="flex flex-col items-start gap-1">
                    <StatusChip status={row.applicant.status} />
                    {row.mobilityCaseId && (
                        <Link
                            href={org.cases.show(row.mobilityCaseId)}
                            className="inline-flex min-h-11 items-center gap-1 text-xs font-semibold text-brand hover:underline"
                        >
                            <Plane aria-hidden className="size-3.5" />
                            {t('mobility.open_case')}
                        </Link>
                    )}
                </span>
            ),
        },
        {
            key: 'assignee',
            header: t('applicants.handled_by'),
            cell: (row) => row.applicant.assigneeName ?? <span className="text-muted">—</span>,
        },
        {
            key: 'applied',
            header: t('applicants.applied'),
            cell: (row) => formatDate(row.applicant.appliedAt),
        },
    ];

    return (
        <>
            <Head title={t('nav.candidates')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.candidates')} description={t('candidates.body')} />
                <div className="grid gap-5 md:grid-cols-[16rem_minmax(0,1fr)]">
                    <FilterSheet
                        activeCount={active}
                        onApply={() => apply(draft)}
                        onClear={() => apply({ job: null, stage: null, q: '' })}
                        className="self-start"
                    >
                        <div className="flex flex-col gap-4">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="candidate-search">{t('candidates.search')}</Label>
                                <Input
                                    id="candidate-search"
                                    type="search"
                                    className="h-11"
                                    value={draft.q}
                                    onChange={(event) =>
                                        setDraft({ ...draft, q: event.target.value })
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="candidate-job">{t('jobs.column.job')}</Label>
                                <NativeSelect
                                    id="candidate-job"
                                    value={draft.job ?? ''}
                                    onChange={(event) =>
                                        setDraft({ ...draft, job: event.target.value || null })
                                    }
                                >
                                    <option value="">{t('candidates.all_jobs')}</option>
                                    {jobs.map((job) => (
                                        <option key={job.code} value={job.code}>
                                            {job.code} · {job.title}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="candidate-stage">{t('applicants.stage')}</Label>
                                <NativeSelect
                                    id="candidate-stage"
                                    value={draft.stage ?? ''}
                                    onChange={(event) =>
                                        setDraft({ ...draft, stage: event.target.value || null })
                                    }
                                >
                                    <option value="">{t('applicants.all')}</option>
                                    {stages.map((stage) => (
                                        <option key={stage} value={stage}>
                                            {t(`status.${stage}`)}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            <Button
                                className="hidden min-h-11 md:inline-flex"
                                onClick={() => apply(draft)}
                            >
                                {t('common.show_results')}
                            </Button>
                        </div>
                    </FilterSheet>
                    <div className="flex min-w-0 flex-col gap-4">
                        <p className="text-sm text-muted" aria-live="polite">
                            {t('candidates.count', { count: formatNumber(candidates.total) })}
                        </p>
                        <DataTable
                            caption={t('nav.candidates')}
                            columns={columns}
                            rows={candidates.data}
                            rowKey={(row) => row.applicant.id}
                            empty={
                                <EmptyState
                                    icon={Users}
                                    title={
                                        active > 0
                                            ? t('candidates.empty_filtered')
                                            : t('candidates.empty_title')
                                    }
                                    description={t('candidates.empty_body')}
                                />
                            }
                        />
                        <Pager page={candidates} />
                    </div>
                </div>
            </div>
        </>
    );
}
