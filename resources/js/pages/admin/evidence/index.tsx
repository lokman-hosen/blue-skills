import { Head, Link } from '@inertiajs/react';
import { FileCheck } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { EvidenceBadge } from '@/components/bs/evidence-badge';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import type { Paginated } from '@/components/bs/jobs/types';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Item = App.Domain.Verification.Data.EvidenceReviewData;

/** Worker documents waiting for a check, oldest first (spec §10, D-090). */
export default function EvidenceQueue({ items }: { items: Paginated<Item> }) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <Head title={t('admin.evidence.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('admin.evidence.title')}
                    description={t('admin.evidence.body')}
                />
                <DataTable<Item>
                    caption={t('admin.evidence.title')}
                    rows={items.data}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={FileCheck}
                            title={t('admin.evidence.empty_title')}
                            description={t('admin.evidence.empty_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'evidence',
                            header: t('admin.evidence.column.evidence'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={admin.evidence.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.title ?? t(`evidence.type.${row.type}`)}
                                    <span className="block text-xs font-normal text-muted">
                                        {t(`evidence.type.${row.type}`)}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'worker',
                            header: t('admin.evidence.column.worker'),
                            cell: (row) => (
                                <span>
                                    {row.workerName}
                                    <span className="block font-mono text-xs text-muted" dir="ltr">
                                        {row.passportReference}
                                    </span>
                                </span>
                            ),
                        },
                        {
                            key: 'state',
                            header: t('admin.organizations.status'),
                            cell: (row) => (
                                <span className="flex flex-col items-start gap-1">
                                    <EvidenceBadge state={row.state.value as EvidenceState} />
                                    {row.reviewer && (
                                        <span className="text-xs text-muted">{row.reviewer}</span>
                                    )}
                                </span>
                            ),
                        },
                        {
                            key: 'uploaded',
                            header: t('admin.evidence.column.uploaded'),
                            cell: (row) => formatDate(row.uploadedAt),
                        },
                    ]}
                />
                <Pager page={items} />
            </div>
        </>
    );
}
