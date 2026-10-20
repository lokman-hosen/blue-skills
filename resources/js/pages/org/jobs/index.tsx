import { Head, Link } from '@inertiajs/react';
import { BriefcaseBusiness, Plus } from 'lucide-react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';

type Props = {
    jobs: App.Domain.Jobs.Data.JobListItemData[];
    canCreate: boolean;
};

/** The organization's jobs with their review status. */
export default function OrgJobs({ jobs, canCreate }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const post = canCreate && (
        <Button asChild className="min-h-11">
            <Link href={JobController.create.url()}>
                <Plus aria-hidden className="size-4" />
                {t('jobs.post')}
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={t('jobs.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('jobs.title')} description={t('jobs.body')} actions={post} />
                <DataTable
                    caption={t('jobs.title')}
                    rows={jobs}
                    rowKey={(row) => row.code}
                    empty={
                        <EmptyState
                            icon={BriefcaseBusiness}
                            title={t('jobs.empty_title')}
                            description={t('jobs.empty_body')}
                            action={post || undefined}
                        />
                    }
                    columns={[
                        {
                            key: 'job',
                            header: t('jobs.column.job'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={JobController.show.url(row.code)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.title}
                                    <span
                                        className="block font-mono text-xs font-normal text-muted"
                                        dir="ltr"
                                    >
                                        {row.code}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'destination',
                            header: t('jobs.field.destination'),
                            cell: (row) =>
                                [row.destinationCity, row.destinationCountryName]
                                    .filter(Boolean)
                                    .join(', '),
                        },
                        {
                            key: 'headcount',
                            header: t('jobs.field.headcount'),
                            align: 'end',
                            cell: (row) => formatNumber(row.headcount),
                        },
                        {
                            key: 'status',
                            header: t('admin.organizations.status'),
                            cell: (row) => (
                                <span className="flex flex-col items-start gap-1">
                                    <StatusChip status={row.status} />
                                    {row.lastDecision?.reasonCode &&
                                        row.status.value !== 'published' && (
                                            <span className="text-xs text-muted">
                                                {t(`review_reason.${row.lastDecision.reasonCode}`)}
                                            </span>
                                        )}
                                </span>
                            ),
                        },
                        {
                            key: 'deadline',
                            header: t('jobs.field.deadline'),
                            cell: (row) =>
                                row.deadline ? formatDate(row.deadline) : t('common.not_disclosed'),
                        },
                    ]}
                />
            </div>
        </>
    );
}
