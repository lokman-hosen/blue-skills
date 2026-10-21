import { Head, Link } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    cohorts: App.Domain.Institute.Data.CohortSummaryData[];
};

/** Every cohort of the institute, newest first. */
export default function Cohorts({ cohorts }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('nav.cohorts')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.cohorts')} description={t('cohorts.body')} />
                <DataTable
                    caption={t('nav.cohorts')}
                    rows={cohorts}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={GraduationCap}
                            title={t('cohorts.empty_title')}
                            description={t('cohorts.empty_body')}
                            action={
                                <Button asChild className="min-h-11">
                                    <Link href={org.courses.index()}>{t('nav.courses')}</Link>
                                </Button>
                            }
                        />
                    }
                    columns={[
                        {
                            key: 'cohort',
                            header: t('cohorts.column.cohort'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={org.cohorts.show(row.id)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.name}
                                    <span className="block text-xs font-normal text-muted">
                                        {row.courseTitle}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'dates',
                            header: t('cohorts.column.dates'),
                            cell: (row) =>
                                row.startsOn && row.endsOn
                                    ? t('cohorts.dates', {
                                          start: formatDate(row.startsOn),
                                          end: formatDate(row.endsOn),
                                      })
                                    : '—',
                        },
                        {
                            key: 'joined',
                            header: t('cohorts.column.joined'),
                            align: 'end',
                            cell: (row) => formatNumber(row.joined),
                        },
                        {
                            key: 'invited',
                            header: t('cohorts.column.waiting'),
                            align: 'end',
                            cell: (row) => formatNumber(row.invited),
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
