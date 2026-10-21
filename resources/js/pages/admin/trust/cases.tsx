import { Head, Link } from '@inertiajs/react';
import { Briefcase } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterPill } from '@/components/bs/filter-pill';
import type { Paginated } from '@/components/bs/jobs/types';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type CaseRow = App.Domain.Trust.Data.TrustCaseListItemData;

type Props = {
    cases: Paginated<CaseRow>;
    state: 'active' | 'mine' | 'closed' | 'all';
};

/** Trust cases (spec §10). */
export default function TrustCases({ cases, state }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('trust.cases.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('trust.cases.title')} description={t('trust.cases.body')} />
                <nav aria-label={t('trust.cases.title')} className="flex flex-wrap gap-1.5">
                    {(['active', 'mine', 'closed', 'all'] as const).map((value) => (
                        <FilterPill
                            key={value}
                            href={admin.trust.cases.index(
                                value === 'active' ? undefined : { query: { state: value } },
                            )}
                            active={state === value}
                            label={t(`trust.cases.state.${value}`)}
                        />
                    ))}
                </nav>
                <DataTable<CaseRow>
                    caption={t('trust.cases.title')}
                    rows={cases.data}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={Briefcase}
                            title={t('trust.cases.empty_title')}
                            description={t('trust.cases.empty_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'case',
                            header: t('trust.cases.column.case'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={admin.trust.cases.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    <span dir="ltr" className="font-mono">
                                        {row.reference}
                                    </span>
                                    <span className="block text-xs font-normal text-muted">
                                        {t(`report.category.${row.category}`)}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'subject',
                            header: t('trust.cases.column.subject'),
                            cell: (row) => (
                                <span>
                                    {row.subject}
                                    <span className="block text-xs text-muted">
                                        {t(`trust.about.${row.subjectType}`)}
                                        {row.subjectSuspended && ` · ${t('trust.suspended')}`}
                                    </span>
                                </span>
                            ),
                        },
                        {
                            key: 'status',
                            header: t('admin.organizations.status'),
                            cell: (row) => (
                                <span className="flex flex-wrap gap-1.5">
                                    <StatusChip status={row.status} />
                                    <StatusChip status={row.priority} icon={false} />
                                </span>
                            ),
                        },
                        {
                            key: 'assignee',
                            header: t('trust.assignee'),
                            cell: (row) => row.assignee ?? <span className="text-muted">—</span>,
                        },
                        {
                            key: 'reports',
                            header: t('trust.cases.column.reports'),
                            align: 'end',
                            cell: (row) => formatNumber(row.reports),
                        },
                        {
                            key: 'opened',
                            header: t('trust.cases.column.opened'),
                            cell: (row) => formatDate(row.openedAt),
                        },
                    ]}
                />
                <Pager page={cases} />
            </div>
        </>
    );
}
