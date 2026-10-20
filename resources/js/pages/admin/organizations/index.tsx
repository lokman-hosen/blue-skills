import { Head, Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import OrganizationReviewController from '@/actions/App/Http/Controllers/Admin/OrganizationReviewController';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';

type Review = App.Domain.Organization.Data.OrganizationReviewData;

/** Registration review queue for employers, agencies and institutes (spec §10). */
export default function OrganizationReviews({ reviews }: { reviews: Review[] }) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('admin.organizations.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('admin.organizations.title')}
                    description={t('admin.organizations.body', {
                        count: formatNumber(reviews.length),
                    })}
                />
                <DataTable
                    caption={t('admin.organizations.title')}
                    rows={reviews}
                    rowKey={(row) => row.reviewId}
                    empty={
                        <EmptyState
                            icon={Building2}
                            title={t('admin.organizations.empty_title')}
                            description={t('admin.organizations.empty_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'organization',
                            header: t('admin.organizations.organization'),
                            primary: true,
                            cell: (row) => (
                                <Link
                                    href={OrganizationReviewController.show.url(row.reviewId)}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {row.displayName}
                                    <span className="block text-xs font-normal text-muted">
                                        {row.legalName}
                                    </span>
                                </Link>
                            ),
                        },
                        {
                            key: 'type',
                            header: t('admin.organizations.type'),
                            cell: (row) => t(`org.type.${row.type}`),
                        },
                        {
                            key: 'country',
                            header: t('admin.organizations.country'),
                            cell: (row) => [row.city, row.countryCode].filter(Boolean).join(', '),
                        },
                        {
                            key: 'evidence',
                            header: t('admin.organizations.documents'),
                            cell: (row) => formatNumber(row.evidenceCount),
                        },
                        {
                            key: 'status',
                            header: t('admin.organizations.status'),
                            cell: (row) => (
                                <span className="flex flex-wrap gap-1">
                                    <StatusChip status={row.reviewStatus} />
                                    {row.flags && (
                                        <StatusChip
                                            tone="warn"
                                            label={t('admin.organizations.flagged')}
                                        />
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
