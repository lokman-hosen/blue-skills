import { Head, Link } from '@inertiajs/react';
import { BriefcaseBusiness, LayoutDashboard, Plus } from 'lucide-react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { EmptyState } from '@/components/bs/empty-state';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';

type Props = {
    counts?: App.Domain.Jobs.Data.JobCountsData | null;
    recentJobs?: App.Domain.Jobs.Data.JobListItemData[];
    canPostJobs?: boolean;
};

/** Organization overview: where the jobs stand and the latest ones (prototype "Overview"). */
export default function OrgDashboard({
    counts = null,
    recentJobs = [],
    canPostJobs = false,
}: Props) {
    const { t, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('nav.dashboard')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.dashboard')}
                    actions={
                        canPostJobs && (
                            <Button asChild className="min-h-11">
                                <Link href={JobController.create.url()}>
                                    <Plus aria-hidden className="size-4" />
                                    {t('jobs.post')}
                                </Link>
                            </Button>
                        )
                    }
                />
                {counts ? (
                    <>
                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <KpiTile
                                label={t('dashboard.jobs.live')}
                                value={formatNumber(counts.live)}
                            />
                            <KpiTile
                                label={t('dashboard.jobs.in_review')}
                                value={formatNumber(counts.inReview)}
                            />
                            <KpiTile
                                label={t('dashboard.jobs.needs_changes')}
                                value={formatNumber(counts.needsChanges)}
                            />
                            <KpiTile
                                label={t('dashboard.jobs.drafts')}
                                value={formatNumber(counts.drafts)}
                            />
                        </div>
                        <section className="rounded-xl border border-line bg-surface p-4">
                            <div className="mb-2 flex items-center justify-between gap-2">
                                <h2 className="eyebrow text-muted">{t('dashboard.jobs.recent')}</h2>
                                <Link
                                    href={JobController.index.url()}
                                    className="inline-flex min-h-11 items-center text-sm font-semibold text-brand"
                                >
                                    {t('dashboard.jobs.all')}
                                </Link>
                            </div>
                            {recentJobs.length === 0 ? (
                                <EmptyState
                                    icon={BriefcaseBusiness}
                                    title={t('jobs.empty_title')}
                                    description={t('jobs.empty_body')}
                                />
                            ) : (
                                <ul className="flex flex-col divide-y divide-line">
                                    {recentJobs.map((job) => (
                                        <li
                                            key={job.code}
                                            className="flex flex-wrap items-center justify-between gap-2 py-2.5"
                                        >
                                            <Link
                                                href={JobController.show.url(job.code)}
                                                className="min-w-0 font-semibold text-brand hover:underline"
                                            >
                                                {job.title}
                                                <span
                                                    className="block font-mono text-xs font-normal text-muted"
                                                    dir="ltr"
                                                >
                                                    {job.code}
                                                </span>
                                            </Link>
                                            <StatusChip status={job.status} />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </>
                ) : (
                    <EmptyState
                        icon={LayoutDashboard}
                        title={t('state.coming_soon.title')}
                        description={t('placeholder.org_dashboard')}
                    />
                )}
            </div>
        </>
    );
}
