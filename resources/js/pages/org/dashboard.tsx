import { Head, Link, usePage } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    CircleAlert,
    FileCheck,
    LayoutDashboard,
    Plane,
    Plus,
} from 'lucide-react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { EmptyState } from '@/components/bs/empty-state';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    counts?: App.Domain.Jobs.Data.JobCountsData | null;
    recentJobs?: App.Domain.Jobs.Data.JobListItemData[];
    canPostJobs?: boolean;
    applicantCounts?: App.Domain.Applications.Data.ApplicantCountsData | null;
    mandateRequests?: number;
    caseCounts?: App.Domain.Mobility.Data.CaseCountsData | null;
    licenceAlert?: { state: 'invalid' | 'expiring'; expiresOn: string | null } | null;
};

/** Organization overview: where the jobs stand and the latest ones (prototype "Overview"). */
export default function OrgDashboard({
    counts = null,
    recentJobs = [],
    canPostJobs = false,
    applicantCounts = null,
    mandateRequests = 0,
    caseCounts = null,
    licenceAlert = null,
}: Props) {
    const { t, formatNumber } = useTranslation();
    const { organizationAbilities } = usePage().props;

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
                {mandateRequests > 0 && (
                    <Link
                        href={org.mandates.index()}
                        className="flex items-center gap-3 rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm font-semibold hover:border-warn"
                    >
                        <FileCheck aria-hidden className="size-5 shrink-0 text-warn" />
                        {t('dashboard.mandate_requests', { count: formatNumber(mandateRequests) })}
                    </Link>
                )}
                {licenceAlert && (
                    <LicenceAlert
                        alert={licenceAlert}
                        canOpen={organizationAbilities.viewMandates}
                    />
                )}
                {caseCounts && caseCounts.open + caseCounts.documentsToCheck > 0 && (
                    <section className="flex flex-col gap-3">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="flex items-center gap-1.5 eyebrow text-muted">
                                <Plane aria-hidden className="size-4" />
                                {t('nav.mobility')}
                            </h2>
                            <Link
                                href={org.cases.index()}
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand"
                            >
                                {t('dashboard.cases.all')}
                            </Link>
                        </div>
                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            {(
                                [
                                    ['open', null, caseCounts.open],
                                    ['documents', 'documents', caseCounts.documentsToCheck],
                                    ['overdue', 'overdue', caseCounts.overdue],
                                    ['on_hold', 'on_hold', caseCounts.onHold],
                                ] as const
                            ).map(([key, attention, value]) => (
                                <Link
                                    key={key}
                                    href={org.cases.index(
                                        attention ? { query: { attention } } : undefined,
                                    )}
                                    className="rounded-xl focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                >
                                    <KpiTile
                                        label={t(`dashboard.cases.${key}`)}
                                        value={formatNumber(value)}
                                        className="h-full hover:border-brand/60"
                                    />
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
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
                        {applicantCounts && (
                            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <KpiTile
                                    label={t('dashboard.applicants.total')}
                                    value={formatNumber(applicantCounts.total)}
                                />
                                <KpiTile
                                    label={t('dashboard.applicants.new')}
                                    value={formatNumber(applicantCounts.new)}
                                />
                                <KpiTile
                                    label={t('dashboard.applicants.shortlisted')}
                                    value={formatNumber(applicantCounts.shortlisted)}
                                />
                                <KpiTile
                                    label={t('dashboard.applicants.interviews')}
                                    value={formatNumber(applicantCounts.interviewsUpcoming)}
                                />
                            </div>
                        )}
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
                                            <span className="flex items-center gap-2">
                                                {job.applicants !== null && job.applicants > 0 && (
                                                    <Link
                                                        href={org.jobs.applicants.url(job.code)}
                                                        className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                                    >
                                                        {t('applicants.view', {
                                                            count: job.applicants,
                                                        })}
                                                    </Link>
                                                )}
                                                <StatusChip status={job.status} />
                                            </span>
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

/** Agency licence warning; links to the licence page for members who can open it (D-080). */
function LicenceAlert({
    alert,
    canOpen,
}: {
    alert: { state: 'invalid' | 'expiring'; expiresOn: string | null };
    canOpen: boolean;
}) {
    const { t, formatDate } = useTranslation();
    const invalid = alert.state === 'invalid';
    const className = invalid
        ? 'flex items-center gap-3 rounded-xl border border-bad/40 bg-bad-soft px-4 py-3 text-sm font-semibold hover:border-bad'
        : 'flex items-center gap-3 rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm font-semibold hover:border-warn';
    const content = (
        <>
            <CircleAlert
                aria-hidden
                className={`size-5 shrink-0 ${invalid ? 'text-bad' : 'text-warn'}`}
            />
            {invalid
                ? t('dashboard.licence.invalid')
                : t('dashboard.licence.expiring', {
                      date: alert.expiresOn ? formatDate(alert.expiresOn) : '',
                  })}
        </>
    );

    return canOpen ? (
        <Link href={org.licence.edit()} className={className}>
            {content}
        </Link>
    ) : (
        <p role="status" className={className}>
            {content}
        </p>
    );
}
