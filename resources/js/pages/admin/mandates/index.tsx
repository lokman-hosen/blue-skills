import { Head, Link } from '@inertiajs/react';
import { FileCheck } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Review = {
    id: string;
    submittedAt: string | null;
    mandate: App.Domain.Jobs.Data.MandateData | null;
};

/** Mandates the employer confirmed, waiting for staff, oldest first (D-074). */
export default function MandateReviews({ reviews }: { reviews: Review[] }) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('admin.mandates.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('admin.mandates.title')}
                    description={t('admin.mandates.body', { count: formatNumber(reviews.length) })}
                />
                <DataTable
                    caption={t('admin.mandates.title')}
                    rows={reviews}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={FileCheck}
                            title={t('admin.mandates.empty_title')}
                            description={t('admin.mandates.empty_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'mandate',
                            header: t('mandates.reference'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={admin.mandates.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.mandate?.agency} → {row.mandate?.employer}
                                    <span
                                        className="block font-mono text-xs font-normal text-muted"
                                        dir="ltr"
                                    >
                                        {row.mandate?.reference}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'scope',
                            header: t('mandates.trades'),
                            cell: (row) =>
                                row.mandate
                                    ? `${row.mandate.occupations.join(', ')} · ${row.mandate.destinationCountryCode ?? ''} · ${formatNumber(row.mandate.headcount)}`
                                    : '—',
                        },
                        {
                            key: 'submitted',
                            header: t('admin.organizations.submitted'),
                            cell: (row) => (row.submittedAt ? formatDate(row.submittedAt) : '—'),
                        },
                    ]}
                />
            </div>
        </>
    );
}
