import { FileText } from 'lucide-react';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';

type Mandate = App.Domain.Jobs.Data.MandateData;

/** Who recruits for whom, for what, where and until when (D-074, product rule 10). */
export function MandateSummary({ mandate }: { mandate: Mandate }) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <section className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="font-mono text-xs text-muted" dir="ltr">
                        {mandate.reference}
                    </p>
                    <p className="font-semibold">
                        {t('mandates.agency_for_employer', {
                            agency: mandate.agency,
                            employer: mandate.employer,
                        })}
                    </p>
                </div>
                <StatusChip status={mandate.status} />
            </div>
            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                <Fact label={t('mandates.trades')}>{mandate.occupations.join(', ') || '—'}</Fact>
                <Fact label={t('jobs.field.destination')}>
                    {mandate.destinationCountryCode ?? '—'}
                </Fact>
                <Fact label={t('mandates.headcount')}>{formatNumber(mandate.headcount)}</Fact>
                <Fact label={t('mandates.valid')}>
                    {[
                        mandate.validFrom && formatDate(mandate.validFrom),
                        mandate.validUntil && formatDate(mandate.validUntil),
                    ]
                        .filter(Boolean)
                        .join(' – ') || '—'}
                </Fact>
                <Fact label={t('mandates.licence')}>
                    <span dir="ltr">{mandate.agencyLicence ?? '—'}</span>
                </Fact>
                <Fact label={t('mandates.jobs')}>{formatNumber(mandate.jobs)}</Fact>
            </dl>
            {mandate.employerNote && (
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-sm">
                    <span className="font-semibold">{t('mandates.employer_note')}: </span>
                    {mandate.employerNote}
                </p>
            )}
            {mandate.evidence.length > 0 && (
                <ul className="flex flex-col gap-1">
                    {mandate.evidence.map((file, index) => (
                        <li key={index}>
                            <a
                                href={file.url ?? undefined}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-brand hover:underline"
                            >
                                <FileText aria-hidden className="size-4" />
                                {file.name}
                            </a>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function Fact({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted">{label}</dt>
            <dd className="font-semibold break-words">{children}</dd>
        </div>
    );
}
