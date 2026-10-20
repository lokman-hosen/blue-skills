import { Head, Link } from '@inertiajs/react';
import { BriefcaseBusiness } from 'lucide-react';
import JobReviewController from '@/actions/App/Http/Controllers/Admin/JobReviewController';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';

type Review = App.Domain.Jobs.Data.JobReviewItemData;

/** Submitted jobs waiting for review, oldest first (spec §10). */
export default function JobReviews({ reviews }: { reviews: Review[] }) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('admin.jobs.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('admin.jobs.title')}
                    description={t('admin.jobs.body', { count: formatNumber(reviews.length) })}
                />
                <DataTable
                    caption={t('admin.jobs.title')}
                    rows={reviews}
                    rowKey={(row) => row.reviewId}
                    empty={
                        <EmptyState
                            icon={BriefcaseBusiness}
                            title={t('admin.jobs.empty_title')}
                            description={t('admin.jobs.empty_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'job',
                            header: t('jobs.column.job'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={JobReviewController.show.url(row.reviewId)}
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
                            key: 'employer',
                            header: t('truth_card.row.employer', {}, 'Employer'),
                            cell: (row) => (
                                <span className="flex flex-col items-start gap-1">
                                    {row.employerName}
                                    <StatusChip status={row.employerVerification} />
                                </span>
                            ),
                        },
                        {
                            key: 'destination',
                            header: t('jobs.field.destination'),
                            cell: (row) => row.destination ?? '',
                        },
                        {
                            key: 'flags',
                            header: t('admin.jobs.checks'),
                            cell: (row) => (
                                <span className="flex flex-wrap gap-1">
                                    {row.notes && (
                                        <StatusChip
                                            tone="warn"
                                            label={t('admin.jobs.material_change')}
                                        />
                                    )}
                                    {row.problemCount > 0 ? (
                                        <StatusChip
                                            tone="bad"
                                            label={t('admin.jobs.problems', {
                                                count: formatNumber(row.problemCount),
                                            })}
                                        />
                                    ) : (
                                        <StatusChip tone="ok" label={t('admin.jobs.rules_ok')} />
                                    )}
                                </span>
                            ),
                        },
                        {
                            key: 'submitted',
                            header: t('admin.organizations.submitted'),
                            cell: (row) => (row.submittedAt ? formatDate(row.submittedAt) : ''),
                        },
                    ]}
                />
            </div>
        </>
    );
}
