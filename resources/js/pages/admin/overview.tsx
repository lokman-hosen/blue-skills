import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Globe2 } from 'lucide-react';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Overview = App.Domain.Trust.Data.AdminOverviewData;

const QUEUE_LINKS: Record<string, NonNullable<InertiaLinkProps['href']> | undefined> = {
    organizations: admin.organizations.index(),
    agencies: admin.organizations.index(),
    institutes: admin.organizations.index(),
    mandates: admin.mandates.index(),
    jobs: admin.jobs.index(),
    evidence: admin.evidence.index(),
    skillshows: admin.skillshows.index(),
    reports: admin.trust.reports.index(),
    leads_due: admin.crm.index({ query: { view: 'due' } }),
};

const MARKETPLACE = [
    'workers',
    'profiles_complete',
    'discoverable',
    'employers',
    'agencies',
    'institutes',
    'live_jobs',
    'applications_30d',
    'offers_accepted',
    'cases_open',
    'joined',
] as const;

/** Staff overview: queues the viewer can work on, and the marketplace (prototype x-queue). */
export default function AdminOverview({ overview }: { overview: Overview }) {
    const { t, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('nav.overview')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.overview')} description={t('admin.overview.body')} />

                {overview.queues.length > 0 && (
                    <section className="flex flex-col gap-3" aria-labelledby="queues-heading">
                        <h2 id="queues-heading" className="eyebrow text-muted">
                            {t('admin.overview.queues')}
                        </h2>
                        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                            {overview.queues.map((queue) => {
                                const href = QUEUE_LINKS[queue.key];
                                const body = (
                                    <>
                                        <span className="block truncate text-xs text-muted">
                                            {t(`admin.overview.queue.${queue.key}`)}
                                        </span>
                                        <span
                                            className={cn(
                                                'mt-1 block font-display text-3xl font-bold tnum',
                                                queue.count > 0 ? 'text-ink' : 'text-muted',
                                            )}
                                        >
                                            {formatNumber(queue.count)}
                                        </span>
                                    </>
                                );

                                return (
                                    <li key={queue.key}>
                                        {href ? (
                                            <Link
                                                href={href}
                                                className="block h-full rounded-xl border border-line bg-surface p-4 hover:border-brand/60"
                                            >
                                                {body}
                                            </Link>
                                        ) : (
                                            <div className="h-full rounded-xl border border-line bg-surface p-4">
                                                {body}
                                            </div>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </section>
                )}

                {overview.corridorsPendingLegalReview > 0 && (
                    <p className="flex items-start gap-2 rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm">
                        <Globe2 aria-hidden className="mt-0.5 size-4 shrink-0 text-warn" />
                        {t('admin.overview.legal_review', {
                            count: formatNumber(overview.corridorsPendingLegalReview),
                        })}
                    </p>
                )}

                <section className="flex flex-col gap-3" aria-labelledby="marketplace-heading">
                    <h2 id="marketplace-heading" className="eyebrow text-muted">
                        {t('admin.overview.marketplace')}
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                        {MARKETPLACE.map((key) => (
                            <KpiTile
                                key={key}
                                label={t(`admin.overview.kpi.${key}`)}
                                value={formatNumber(overview.marketplace[key] ?? 0)}
                            />
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
