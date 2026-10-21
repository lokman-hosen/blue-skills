import { Head, Link } from '@inertiajs/react';
import { FileCheck, Plus } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    mandates: App.Domain.Jobs.Data.MandateData[];
    side: 'agency' | 'employer';
    canRequest: boolean;
    licenceValid: boolean;
};

/** Mandates between agencies and employers (D-074). */
export default function Mandates({ mandates, side, canRequest, licenceValid }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('nav.mandates')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.mandates')}
                    description={
                        side === 'agency' ? t('mandates.agency_body') : t('mandates.employer_body')
                    }
                    actions={
                        canRequest && (
                            <Button asChild className="min-h-11">
                                <Link href={org.mandates.create()}>
                                    <Plus aria-hidden className="size-4" />
                                    {t('mandates.request')}
                                </Link>
                            </Button>
                        )
                    }
                />
                {side === 'agency' && !licenceValid && (
                    <p
                        role="alert"
                        className="rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm"
                    >
                        {t('mandates.licence_warning')}{' '}
                        <Link
                            href={org.licence.edit()}
                            className="font-semibold text-brand hover:underline"
                        >
                            {t('licence.title')}
                        </Link>
                    </p>
                )}
                <DataTable
                    caption={t('nav.mandates')}
                    rows={mandates}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={FileCheck}
                            title={t('mandates.empty_title')}
                            description={
                                side === 'agency'
                                    ? t('mandates.empty_agency')
                                    : t('mandates.empty_employer')
                            }
                        />
                    }
                    columns={[
                        {
                            key: 'reference',
                            header: t('mandates.reference'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={org.mandates.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {side === 'agency' ? row.employer : row.agency}
                                    <span
                                        className="block font-mono text-xs font-normal text-muted"
                                        dir="ltr"
                                    >
                                        {row.reference}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'scope',
                            header: t('mandates.trades'),
                            cell: (row) =>
                                `${row.occupations.join(', ')} · ${row.destinationCountryCode ?? ''} · ${formatNumber(row.headcount)}`,
                        },
                        {
                            key: 'until',
                            header: t('mandates.valid_until'),
                            cell: (row) => (row.validUntil ? formatDate(row.validUntil) : '—'),
                        },
                        {
                            key: 'status',
                            header: t('admin.organizations.status'),
                            cell: (row) => <StatusChip status={row.status} />,
                        },
                    ]}
                />
            </div>
        </>
    );
}
