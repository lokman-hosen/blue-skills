import { Link } from '@inertiajs/react';
import { BadgeCheck, CalendarClock, House, MapPin, Wallet } from 'lucide-react';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import jobs from '@/routes/jobs';

/** One search result: headline Truth Card facts. Missing values read "Not disclosed". */
export function JobListingCard({ job }: { job: App.Domain.Jobs.Data.JobListingData }) {
    const { t, formatMoney, formatDate, formatNumber } = useTranslation();
    const notDisclosed = <span className="text-warn italic">{t('common.not_disclosed')}</span>;

    return (
        <article className="flex min-w-0 flex-col gap-3 rounded-xl border border-line bg-surface p-4">
            <header className="flex flex-wrap items-start justify-between gap-2">
                <div className="min-w-0">
                    <h3 className="font-display text-lg leading-tight font-bold">
                        <Link href={jobs.show.url(job.code)} className="hover:underline">
                            {job.title}
                        </Link>
                    </h3>
                    <p className="flex flex-wrap items-center gap-1 text-sm text-muted">
                        {job.employerName}
                        {job.employerVerified && (
                            <BadgeCheck
                                aria-label={t('truth_card.check.employer_verified')}
                                className="size-4 text-ok"
                            />
                        )}
                    </p>
                </div>
                <span className="font-mono text-[11px] text-muted" dir="ltr">
                    {job.code}
                </span>
            </header>
            <dl className="grid gap-x-4 gap-y-1.5 text-sm sm:grid-cols-2">
                <div className="flex items-center gap-2">
                    <dt className="sr-only">{t('jobs.field.destination')}</dt>
                    <MapPin aria-hidden className="size-4 shrink-0 text-muted" />
                    <dd>
                        {[job.destinationCity, job.destinationCountryName]
                            .filter(Boolean)
                            .join(', ')}
                    </dd>
                </div>
                <div className="flex items-center gap-2">
                    <dt className="sr-only">{t('jobs.field.salary')}</dt>
                    <Wallet aria-hidden className="size-4 shrink-0 text-muted" />
                    <dd className="font-semibold">
                        {job.salary
                            ? `${formatMoney(job.salary)}${job.salaryPeriod ? ` / ${t(`salary_period.${job.salaryPeriod}`)}` : ''}`
                            : notDisclosed}
                    </dd>
                </div>
                <div className="flex items-center gap-2">
                    <dt className="sr-only">{t('truth_card.benefit.accommodation')}</dt>
                    <House aria-hidden className="size-4 shrink-0 text-muted" />
                    <dd>
                        {t('truth_card.benefit.accommodation')}:{' '}
                        {job.accommodation
                            ? t(`truth_card.provision.${job.accommodation}`)
                            : notDisclosed}
                    </dd>
                </div>
                <div className="flex items-center gap-2">
                    <dt className="sr-only">{t('jobs.field.deadline')}</dt>
                    <CalendarClock aria-hidden className="size-4 shrink-0 text-muted" />
                    <dd>
                        {job.deadline
                            ? t('jobs.listing.apply_by', { date: formatDate(job.deadline) })
                            : t('jobs.listing.no_deadline')}
                    </dd>
                </div>
            </dl>
            <footer className="flex flex-wrap items-center gap-1.5">
                <StatusChip
                    tone={job.workerPaysCosts ? 'warn' : 'ok'}
                    label={
                        job.workerPaysCosts
                            ? t('jobs.listing.worker_pays')
                            : t('jobs.listing.no_worker_costs')
                    }
                />
                {job.match && (
                    <StatusChip
                        tone="brand"
                        icon={false}
                        label={t('jobs.match.summary', {
                            met: formatNumber(job.match.met),
                            total: formatNumber(job.match.total),
                        })}
                    />
                )}
            </footer>
        </article>
    );
}
