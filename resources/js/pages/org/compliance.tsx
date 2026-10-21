import { Head, Link } from '@inertiajs/react';
import { CircleAlert, FileCheck, IdCard, PauseCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { DataTable } from '@/components/bs/data-table';
import type { DataTableColumn } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Coverage = App.Domain.Jobs.Data.MandateCoverageData;

type Props = {
    licence: {
        number: string | null;
        expiresOn: string | null;
        valid: boolean;
        expiringSoon: boolean;
        countries: string[];
    };
    mandates: Coverage[];
    uncoveredJobs: App.Domain.Jobs.Data.JobListItemData[];
    cases: App.Domain.Mobility.Data.CaseCountsData;
};

/** The agency's compliance overview: licence, mandates and the jobs they back (D-080). */
export default function Compliance({ licence, mandates, uncoveredJobs, cases }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    const columns: DataTableColumn<Coverage>[] = [
        {
            key: 'mandate',
            header: t('compliance.mandate'),
            primary: true,
            cell: (row) => (
                <Link
                    href={org.mandates.show(row.mandate.id)}
                    className="font-semibold text-brand hover:underline"
                >
                    <span dir="ltr">{row.mandate.reference}</span>
                    <span className="block text-xs font-normal text-muted">
                        {row.mandate.employer}
                    </span>
                </Link>
            ),
        },
        {
            key: 'destination',
            header: t('jobs.field.destination'),
            cell: (row) => row.mandate.destinationCountryCode ?? '—',
        },
        {
            key: 'status',
            header: t('admin.organizations.status'),
            cell: (row) => (
                <span className="flex flex-col items-start gap-1">
                    <StatusChip status={row.mandate.status} />
                    {row.mandate.status.value === 'verified' && !row.mandate.usable && (
                        <span className="text-xs text-bad">{t('compliance.not_usable')}</span>
                    )}
                </span>
            ),
        },
        {
            key: 'until',
            header: t('compliance.valid_until'),
            cell: (row) =>
                row.mandate.validUntil ? (
                    <span className={row.expiringSoon ? 'font-semibold text-warn' : undefined}>
                        {formatDate(row.mandate.validUntil)}
                        {row.expiringSoon && (
                            <span className="block text-xs">{t('compliance.ends_soon')}</span>
                        )}
                    </span>
                ) : (
                    '—'
                ),
        },
        {
            key: 'places',
            header: t('compliance.places'),
            align: 'end',
            cell: (row) =>
                t('compliance.places_left', {
                    left: formatNumber(row.placesLeft),
                    total: formatNumber(row.mandate.headcount),
                }),
        },
        {
            key: 'jobs',
            header: t('compliance.live_jobs'),
            align: 'end',
            cell: (row) => formatNumber(row.liveJobs),
        },
    ];

    return (
        <>
            <Head title={t('nav.compliance')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.compliance')} description={t('compliance.body')} />

                <div className="grid gap-5 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
                    <Panel
                        title={t('compliance.licence')}
                        icon={<IdCard aria-hidden className="size-4" />}
                    >
                        <dl className="grid gap-2 text-sm">
                            <Row label={t('licence.number')}>
                                <span dir="ltr">{licence.number ?? t('common.not_disclosed')}</span>
                            </Row>
                            <Row label={t('licence.expires')}>
                                {licence.expiresOn ? formatDate(licence.expiresOn) : '—'}
                            </Row>
                            <Row label={t('licence.countries')}>
                                {licence.countries.length > 0 ? licence.countries.join(', ') : '—'}
                            </Row>
                        </dl>
                        <StatusChip
                            className="self-start"
                            tone={!licence.valid ? 'bad' : licence.expiringSoon ? 'warn' : 'ok'}
                            label={
                                !licence.valid
                                    ? t('compliance.licence_invalid')
                                    : licence.expiringSoon
                                      ? t('compliance.licence_expiring')
                                      : t('compliance.licence_valid')
                            }
                        />
                        <Button asChild variant="outline" className="min-h-11 self-start">
                            <Link href={org.licence.edit()}>{t('compliance.open_licence')}</Link>
                        </Button>
                    </Panel>

                    <Panel title={t('compliance.cases')}>
                        <ul className="grid gap-2 sm:grid-cols-2">
                            {(
                                [
                                    ['documents', cases.documentsToCheck],
                                    ['overdue', cases.overdue],
                                    ['on_hold', cases.onHold],
                                ] as const
                            ).map(([key, count]) => (
                                <li key={key}>
                                    <Link
                                        href={org.cases.index({ query: { attention: key } })}
                                        className="flex min-h-11 items-center justify-between gap-2 rounded-xl border border-line px-3 py-2 text-sm hover:border-brand/60"
                                    >
                                        <span>{t(`mobility.list.attention.${key}`)}</span>
                                        <span
                                            className={
                                                count > 0
                                                    ? 'font-semibold text-warn tnum'
                                                    : 'text-muted tnum'
                                            }
                                        >
                                            {formatNumber(count)}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                            <li>
                                <Link
                                    href={org.cases.index()}
                                    className="flex min-h-11 items-center justify-between gap-2 rounded-xl border border-line px-3 py-2 text-sm hover:border-brand/60"
                                >
                                    <span>{t('mobility.list.state.open')}</span>
                                    <span className="tnum">{formatNumber(cases.open)}</span>
                                </Link>
                            </li>
                        </ul>
                    </Panel>
                </div>

                {uncoveredJobs.length > 0 && (
                    <section className="flex flex-col gap-3 rounded-2xl border border-bad/40 bg-bad-soft p-4 sm:p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <CircleAlert aria-hidden className="size-4 text-bad" />
                            {t('compliance.uncovered_title')}
                        </h2>
                        <p className="text-sm">{t('compliance.uncovered_body')}</p>
                        <ul className="flex flex-col divide-y divide-line rounded-xl bg-surface px-3">
                            {uncoveredJobs.map((job) => (
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
                                            {job.mandateReference && ` · ${job.mandateReference}`}
                                        </span>
                                    </Link>
                                    <span className="inline-flex items-center gap-1.5">
                                        {job.status.value === 'paused' && (
                                            <PauseCircle
                                                aria-hidden
                                                className="size-4 text-muted"
                                            />
                                        )}
                                        <StatusChip status={job.status} />
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="flex flex-col gap-3">
                    <h2 className="eyebrow text-muted">{t('compliance.mandates')}</h2>
                    <DataTable
                        caption={t('compliance.mandates')}
                        columns={columns}
                        rows={mandates}
                        rowKey={(row) => row.mandate.id}
                        empty={
                            <EmptyState
                                icon={FileCheck}
                                title={t('compliance.no_mandates')}
                                description={t('compliance.no_mandates_body')}
                                action={
                                    <Button asChild className="min-h-11">
                                        <Link href={org.mandates.index()}>{t('nav.mandates')}</Link>
                                    </Button>
                                }
                            />
                        }
                    />
                </section>
            </div>
        </>
    );
}

function Panel({
    title,
    icon,
    children,
}: {
    title: string;
    icon?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="flex items-center gap-1.5 eyebrow text-muted">
                {icon}
                {title}
            </h2>
            {children}
        </section>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex flex-wrap justify-between gap-x-3">
            <dt className="text-muted">{label}</dt>
            <dd className="min-w-0 text-end font-semibold break-words">{children}</dd>
        </div>
    );
}
