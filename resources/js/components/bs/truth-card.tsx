import type { LucideIcon } from 'lucide-react';
import {
    Building2,
    BriefcaseBusiness,
    CircleAlert,
    FileText,
    Flag,
    House,
    ListChecks,
    Plane,
    ShieldCheck,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusChip } from './status-chip';
import { isTone } from './tone';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type JobTruthCard = App.Domain.Jobs.Data.JobTruthCardData;

type TruthCardProps = {
    job: JobTruthCard;
    /** Employer-side preview: hides the report control. */
    preview?: boolean;
    onReport?: () => void;
    className?: string;
};

/**
 * The worker-facing Job Truth Card (spec §5, product rule 4). Same layout everywhere.
 * Missing values always read "Not disclosed"; nothing is inferred or guessed here.
 */
export function TruthCard({ job, preview = false, onReport, className }: TruthCardProps) {
    const { t, formatMoney, formatDate, formatNumber } = useTranslation();

    const notDisclosed = (
        <span className="font-medium text-warn italic">{t('common.not_disclosed')}</span>
    );
    const orNotDisclosed = (value: ReactNode | null | undefined) =>
        value === null || value === undefined || value === '' ? notDisclosed : value;
    const payer = (value: App.Domain.Jobs.Enums.CostPayer | null) =>
        value ? t(`truth_card.payer.${value}`) : notDisclosed;
    /** An option label, or the poster's own words when they chose "Other" (D-117). */
    const option = (key: string, value: string | null, other: string | null) =>
        value === null
            ? null
            : value === 'other'
              ? (other ?? t(`jobs.option.${key}.other`))
              : t(`jobs.option.${key}.${value}`);
    const hours =
        job.workingHours || job.workingDays
            ? [
                  option('working_hours', job.workingHours, job.workingHoursOther),
                  option('working_days', job.workingDays, job.workingDaysOther),
              ]
                  .filter(Boolean)
                  .join(' · ')
            : job.hoursPerDay !== null && job.daysPerWeek !== null
              ? t('truth_card.value.hours', {
                    hours: formatNumber(job.hoursPerDay),
                    days: formatNumber(job.daysPerWeek),
                })
              : null;
    const contract = job.contractDuration
        ? option('contract_duration', job.contractDuration, job.contractOther)
        : job.contractMonths !== null
          ? t('truth_card.value.contract', { months: formatNumber(job.contractMonths) })
          : null;
    const experience = job.experienceLevel
        ? option('experience_level', job.experienceLevel, job.experienceOther)
        : job.minExperienceYears !== null
          ? t('truth_card.value.experience', { years: formatNumber(job.minExperienceYears) })
          : null;
    const languageLevel = (level: string) =>
        ['basic', 'communicative', 'fluent'].includes(level)
            ? t(`jobs.language_level.${level}`)
            : level;

    const location = [job.destinationCity, job.destinationCountryName ?? job.destinationCountryCode]
        .filter(Boolean)
        .join(', ');
    const workerCosts = job.costs.filter((cost) => cost.payer === 'worker');

    return (
        <article
            className={cn('overflow-hidden rounded-2xl border border-line bg-surface', className)}
        >
            <header className="bg-brand-soft px-4 py-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="eyebrow text-brand">{t('truth_card.title')}</p>
                        <h3 className="mt-1 font-display text-xl leading-tight font-bold break-words">
                            {job.title}
                        </h3>
                        <p className="mt-0.5 text-[13px] text-muted">
                            {location} · {job.employerName}
                        </p>
                    </div>
                    <span
                        className="shrink-0 rounded-md border border-line bg-surface px-2 py-1 font-mono text-[11px] font-bold"
                        dir="ltr"
                    >
                        <span className="sr-only">{t('truth_card.job_id')}: </span>
                        {job.publicCode}
                    </span>
                </div>
                {job.checks.length > 0 && (
                    <ul className="mt-3 flex flex-wrap gap-1.5">
                        {job.checks.map((check) => (
                            <li key={check.key} className="max-w-full">
                                <StatusChip
                                    label={t(`truth_card.check.${check.key}`)}
                                    tone={isTone(check.tone) ? check.tone : 'neutral'}
                                    icon={check.tone === 'ok' ? ShieldCheck : undefined}
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </header>

            <div className="divide-y divide-line">
                <Section title={t('truth_card.section.parties')} icon={Building2}>
                    <Row label={t('truth_card.row.employer')}>{job.employerName}</Row>
                    <Row label={t('truth_card.row.posted_by')}>
                        {t(
                            job.postedByAgency
                                ? 'truth_card.value.posted_by_agency'
                                : 'truth_card.value.posted_by_employer',
                            { name: job.postedByName },
                        )}
                    </Row>
                    {job.agencyLicenceNumber && (
                        <Row label={t('truth_card.row.agency_licence')}>
                            {job.agencyLicenceExpiresOn
                                ? t('truth_card.value.licence', {
                                      number: job.agencyLicenceNumber,
                                      date: formatDate(job.agencyLicenceExpiresOn, {
                                          month: 'short',
                                          year: 'numeric',
                                      }),
                                  })
                                : job.agencyLicenceNumber}
                        </Row>
                    )}
                    <Row label={t('truth_card.row.mandate')}>
                        {job.mandateReference
                            ? t('truth_card.value.mandate', {
                                  reference: job.mandateReference,
                                  headcount: formatNumber(job.mandateHeadcount ?? 0),
                              })
                            : job.postedByAgency
                              ? notDisclosed
                              : t('truth_card.value.direct_hire')}
                    </Row>
                </Section>

                <Section title={t('truth_card.section.work')} icon={BriefcaseBusiness}>
                    {(job.industry || job.occupation) && (
                        <Row label={t('truth_card.row.industry')}>
                            {[job.industry, job.occupation].filter(Boolean).join(' · ')}
                        </Row>
                    )}
                    <Row label={t('truth_card.row.employment_type')}>
                        {orNotDisclosed(option('employment_type', job.employmentType, null))}
                    </Row>
                    <Row label={t('truth_card.row.salary')}>
                        {job.salary ? (
                            <span className="tnum">
                                {job.salaryMax &&
                                job.salaryMax.amountMinor !== job.salary.amountMinor
                                    ? t('truth_card.value.salary_range', {
                                          min: formatMoney(job.salary),
                                          max: formatMoney(job.salaryMax),
                                      })
                                    : formatMoney(job.salary)}
                                {job.salaryPeriod &&
                                    ` ${t(`truth_card.period.${job.salaryPeriod}`)}`}
                            </span>
                        ) : (
                            notDisclosed
                        )}
                    </Row>
                    <Row label={t('truth_card.row.pay_basis')}>
                        {orNotDisclosed(option('salary_basis', job.salaryBasis, null))}
                    </Row>
                    <Row label={t('truth_card.row.hours')}>{orNotDisclosed(hours)}</Row>
                    <Row label={t('truth_card.row.shift')}>
                        {orNotDisclosed(
                            option('shift_pattern', job.shiftPattern, job.shiftPatternOther),
                        )}
                    </Row>
                    <Row label={t('truth_card.row.overtime')}>
                        {orNotDisclosed(job.overtimeTerms)}
                    </Row>
                    <Row label={t('truth_card.row.contract')}>{orNotDisclosed(contract)}</Row>
                    <Row label={t('truth_card.row.openings')}>{formatNumber(job.headcount)}</Row>
                    {(job.joiningFrom || job.joiningTo) && (
                        <Row label={t('truth_card.row.joining')}>
                            {t('truth_card.value.joining', {
                                from: job.joiningFrom ? formatDate(job.joiningFrom) : '…',
                                to: job.joiningTo ? formatDate(job.joiningTo) : '…',
                            })}
                        </Row>
                    )}
                </Section>

                <Section title={t('truth_card.section.requirements')} icon={ListChecks}>
                    <Row label={t('truth_card.row.experience')}>{orNotDisclosed(experience)}</Row>
                    <Row label={t('truth_card.row.education')}>
                        {orNotDisclosed(
                            option('education_level', job.educationLevel, job.educationOther),
                        )}
                    </Row>
                    {job.ageRange && (
                        // Shown only when the employer states one; never used for matching (D-117).
                        <Row label={t('truth_card.row.age')}>
                            {option('age_range', job.ageRange, job.ageOther)}
                        </Row>
                    )}
                    <Row label={t('truth_card.row.skills')}>
                        {job.skills.length > 0 || job.otherSkills
                            ? [...job.skills, job.otherSkills].filter(Boolean).join(', ')
                            : notDisclosed}
                    </Row>
                    <Row label={t('truth_card.row.languages')}>
                        {job.languageRequirements.length > 0
                            ? job.languageRequirements
                                  .map(
                                      (language) =>
                                          `${language.code.toUpperCase()} (${languageLevel(language.level)})`,
                                  )
                                  .join(' · ')
                            : notDisclosed}
                    </Row>
                </Section>

                {(job.duties || job.additionalDetails) && (
                    <Section title={t('truth_card.section.details')} icon={FileText}>
                        {job.duties && (
                            <Row label={t('truth_card.row.duties')}>
                                <span className="whitespace-pre-line">{job.duties}</span>
                            </Row>
                        )}
                        {job.additionalDetails && (
                            <Row label={t('truth_card.row.additional_details')}>
                                <span className="whitespace-pre-line">{job.additionalDetails}</span>
                            </Row>
                        )}
                    </Section>
                )}

                <Section title={t('truth_card.section.benefits')} icon={House}>
                    {job.benefits.map((benefit) => (
                        <Row key={benefit.key} label={t(`truth_card.benefit.${benefit.key}`)}>
                            {benefit.provision ? (
                                <>
                                    {t(`truth_card.provision.${benefit.provision}`)}
                                    {benefit.key === 'accommodation' &&
                                        benefit.provision === 'deducted' &&
                                        job.accommodationCost && (
                                            <span className="font-semibold tnum">
                                                {' · '}
                                                {t('truth_card.value.per_month', {
                                                    amount: formatMoney(job.accommodationCost),
                                                })}
                                            </span>
                                        )}
                                    {benefit.details && (
                                        <span className="text-muted"> · {benefit.details}</span>
                                    )}
                                </>
                            ) : (
                                notDisclosed
                            )}
                        </Row>
                    ))}
                    {job.otherBenefits && (
                        <Row label={t('truth_card.row.other_benefits')}>{job.otherBenefits}</Row>
                    )}
                </Section>

                <Section title={t('truth_card.section.visa')} icon={Plane}>
                    <Row label={t('truth_card.row.permit_payer')}>{payer(job.permitPayer)}</Row>
                    <Row label={t('truth_card.row.visa_payer')}>{payer(job.visaPayer)}</Row>
                </Section>

                <Section title={t('truth_card.section.costs')} icon={CircleAlert}>
                    <Row label={t('truth_card.row.worker_fees')}>
                        {orNotDisclosed(option('worker_fees', job.workerFees, null))}
                    </Row>
                    {workerCosts.length > 0 ? (
                        workerCosts.map((cost, index) => (
                            <Row
                                key={index}
                                label={cost.description ?? t(`truth_card.cost.${cost.category}`)}
                            >
                                <span className="font-semibold tnum">
                                    {formatMoney(cost.amount)}
                                </span>
                                <span className="text-muted">
                                    {' · '}
                                    {t('truth_card.value.pays', {
                                        payer: t(`truth_card.payer.${cost.payer}`),
                                    })}
                                </span>
                            </Row>
                        ))
                    ) : (
                        <Row label={t('truth_card.payer.worker')}>
                            <span className="font-semibold text-ok">
                                {t('truth_card.costs.none')}
                            </span>
                        </Row>
                    )}
                </Section>
            </div>

            <footer className="flex flex-wrap items-center justify-between gap-2 bg-surface-2/60 px-4 py-3 text-xs text-muted">
                <span>
                    {job.deadline
                        ? t('truth_card.apply_by', { date: formatDate(job.deadline) })
                        : t('truth_card.apply_by', { date: t('common.not_disclosed') })}
                </span>
                {preview ? (
                    <span>{t('truth_card.preview')}</span>
                ) : (
                    <button
                        type="button"
                        onClick={onReport}
                        className="inline-flex min-h-11 items-center gap-1 font-semibold text-bad hover:underline"
                    >
                        <Flag aria-hidden className="size-3.5" />
                        {t('truth_card.report')}
                    </button>
                )}
            </footer>
        </article>
    );
}

function Section({
    title,
    icon: Icon,
    children,
}: {
    title: string;
    icon: LucideIcon;
    children: ReactNode;
}) {
    return (
        <section className="px-4 py-3">
            <h4 className="mb-1 flex items-center gap-1.5 eyebrow text-muted">
                <Icon aria-hidden className="size-3.5" />
                {title}
            </h4>
            <dl>{children}</dl>
        </section>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid grid-cols-[minmax(0,40%)_minmax(0,1fr)] gap-3 border-b border-line/70 py-2 last:border-0">
            <dt className="text-[13px] text-muted">{label}</dt>
            <dd className="min-w-0 text-[13px] font-medium break-words">{children}</dd>
        </div>
    );
}
