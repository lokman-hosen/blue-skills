import { Head, Link } from '@inertiajs/react';
import { BarChart3 } from 'lucide-react';
import type { ReactNode } from 'react';
import { RangeForm } from '@/components/bs/crm/range-form';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { KpiTile } from '@/components/bs/kpi-tile';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Analytics = App.Domain.Jobs.Data.HiringAnalyticsData;
type JobRow = Analytics['jobs'][number];

type Props = {
    analytics: Analytics;
    range: { from: string; to: string };
};

const JOB_STAGES = ['applied', 'qualified', 'interview', 'selected', 'joined'] as const;

/** Hiring analytics: counts and timings for a date range, never scores (spec §14). */
export default function HiringAnalytics({ analytics, range }: Props) {
    const { t, formatNumber } = useTranslation();
    const applied = analytics.stages.find((stage) => stage.key === 'applied')?.count ?? 0;
    const maxStage = Math.max(1, ...analytics.stages.map((stage) => stage.count));
    const maxCountry = Math.max(1, ...analytics.countries.map((country) => country.count));
    const days = (value: number | null) =>
        value === null ? '—' : t('analytics.days', { count: formatNumber(value) });

    return (
        <>
            <Head title={t('nav.analytics')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.analytics')} description={t('analytics.body')} />
                <RangeForm range={range} url={(next) => org.analytics.index.url({ query: next })} />

                <ul className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <li>
                        <KpiTile
                            label={t('analytics.live_jobs')}
                            value={formatNumber(analytics.liveJobs)}
                        />
                    </li>
                    <li>
                        <KpiTile
                            label={t('analytics.applications')}
                            value={formatNumber(applied)}
                        />
                    </li>
                    <li>
                        <KpiTile
                            label={t('analytics.time_to_shortlist')}
                            value={days(analytics.medianDaysToShortlist)}
                            sub={t('analytics.median')}
                        />
                    </li>
                    <li>
                        <KpiTile
                            label={t('analytics.time_to_offer')}
                            value={days(analytics.medianDaysToOffer)}
                            sub={t('analytics.median')}
                        />
                    </li>
                </ul>

                {applied === 0 ? (
                    <EmptyState
                        icon={BarChart3}
                        title={t('analytics.empty_title')}
                        description={t('analytics.empty_body')}
                    />
                ) : (
                    <>
                        <div className="grid gap-5 lg:grid-cols-2">
                            <Panel title={t('analytics.stages')}>
                                <ol className="flex flex-col gap-2.5">
                                    {analytics.stages.map((stage) => (
                                        <Bar
                                            key={stage.key}
                                            label={t(`projects.stage.${stage.key}`)}
                                            value={stage.count}
                                            max={maxStage}
                                            display={formatNumber(stage.count)}
                                        />
                                    ))}
                                </ol>
                                <p className="text-xs text-muted">{t('analytics.stages_note')}</p>
                            </Panel>
                            <Panel title={t('analytics.where_from')}>
                                <ol className="flex flex-col gap-2.5">
                                    {analytics.countries.map((country) => (
                                        <Bar
                                            key={country.code}
                                            label={country.code}
                                            value={country.count}
                                            max={maxCountry}
                                            display={formatNumber(country.count)}
                                        />
                                    ))}
                                </ol>
                                <dl className="grid grid-cols-3 gap-2 border-t border-line pt-3 text-sm">
                                    {(['direct', 'invited', 'withdrawn'] as const).map((key) => (
                                        <div key={key} className="flex flex-col">
                                            <dt className="text-xs text-muted">
                                                {t(`analytics.source.${key}`)}
                                            </dt>
                                            <dd className="font-semibold tnum">
                                                {formatNumber(analytics.sources[key])}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </Panel>
                        </div>

                        <Panel title={t('analytics.by_job')}>
                            <DataTable<JobRow>
                                caption={t('analytics.by_job')}
                                rows={analytics.jobs}
                                rowKey={(row) => row.id}
                                columns={[
                                    {
                                        key: 'job',
                                        header: t('projects.column.job'),
                                        primary: true,
                                        cell: (row) => (
                                            <Link
                                                href={org.jobs.applicants.url(row.code)}
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
                                        key: 'status',
                                        header: t('admin.organizations.status'),
                                        cell: (row) => <StatusChip status={row.status} />,
                                    },
                                    ...JOB_STAGES.map((stage) => ({
                                        key: stage,
                                        header: t(`projects.stage.${stage}`),
                                        align: 'end' as const,
                                        cell: (row: JobRow) => (
                                            <span className="tnum">
                                                {formatNumber(row.counts[stage] ?? 0)}
                                            </span>
                                        ),
                                    })),
                                ]}
                            />
                        </Panel>
                    </>
                )}

                {analytics.projects.length > 0 && (
                    <Panel title={t('analytics.projects')}>
                        <ul className="flex flex-col divide-y divide-line">
                            {analytics.projects.map((project) => (
                                <li
                                    key={project.id}
                                    className="flex flex-wrap items-center justify-between gap-2 py-2.5"
                                >
                                    <Link
                                        href={org.projects.show(project.id)}
                                        className="font-semibold text-brand hover:underline"
                                    >
                                        {project.name}
                                    </Link>
                                    <span className="text-sm tnum">
                                        {t('analytics.joined_of', {
                                            joined: formatNumber(project.joined),
                                            target: formatNumber(project.targetHeadcount),
                                        })}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function Bar({
    label,
    value,
    max,
    display,
}: {
    label: string;
    value: number;
    max: number;
    display: string;
}) {
    return (
        <li className="flex flex-col gap-1">
            <span className="flex items-baseline justify-between gap-3 text-sm">
                <span>{label}</span>
                <span className="font-semibold tnum">{display}</span>
            </span>
            <span aria-hidden className="h-2 overflow-hidden rounded-full bg-surface-2">
                <span
                    className="block h-full rounded-full bg-brand"
                    style={{ width: `${(value / max) * 100}%` }}
                />
            </span>
        </li>
    );
}
