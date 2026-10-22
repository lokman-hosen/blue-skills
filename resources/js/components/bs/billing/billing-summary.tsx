import { KpiTile } from '@/components/bs/kpi-tile';
import { useTranslation } from '@/lib/i18n';

type Billing = App.Domain.Billing.Data.OrganizationBillingData;

/** Plan, posting credits, seats and the credit history (spec §11, D-095). */
export function BillingSummary({ billing }: { billing: Billing }) {
    const { t, formatDate, formatMoney, formatNumber } = useTranslation();
    const plan = billing.plan;

    return (
        <div className="flex flex-col gap-5">
            {!billing.enforced && (
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-sm text-muted">
                    {t('billing.not_enforced')}
                </p>
            )}
            <ul className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <li>
                    <KpiTile
                        label={t('billing.plan')}
                        value={plan ? plan.name : t('billing.no_plan')}
                        sub={
                            plan
                                ? `${formatMoney(plan.price)} · ${t(`billing_period.${plan.billingPeriod}`)}`
                                : undefined
                        }
                    />
                </li>
                <li>
                    <KpiTile
                        label={t('billing.credits')}
                        value={formatNumber(billing.credits)}
                        sub={
                            billing.credits < 0
                                ? t('billing.credits_owed')
                                : t('billing.credits_hint')
                        }
                        className={billing.credits < 0 ? 'border-bad/50' : undefined}
                    />
                </li>
                <li>
                    <KpiTile
                        label={t('billing.seats')}
                        value={
                            billing.seats === null
                                ? formatNumber(billing.seatsUsed)
                                : `${formatNumber(billing.seatsUsed)} / ${formatNumber(billing.seats)}`
                        }
                        sub={
                            billing.seats === null
                                ? t('billing.seats_unlimited')
                                : t('billing.seats_hint')
                        }
                    />
                </li>
            </ul>
            {plan && (
                <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4 sm:p-5">
                    <h2 className="eyebrow text-muted">{t('billing.includes')}</h2>
                    <ul className="flex flex-wrap gap-x-5 gap-y-1 text-sm">
                        <li>
                            {t('billing.feature.credits', {
                                count: formatNumber(plan.postingCredits),
                            })}
                        </li>
                        <li>
                            {plan.seats === null
                                ? t('billing.seats_unlimited')
                                : t('billing.feature.seats', { count: formatNumber(plan.seats) })}
                        </li>
                        <li>
                            {plan.talentSearch
                                ? t('billing.feature.talent_search')
                                : t('billing.feature.no_talent_search')}
                        </li>
                        <li>
                            {plan.recruitmentProjects
                                ? t('billing.feature.projects')
                                : t('billing.feature.no_projects')}
                        </li>
                    </ul>
                    {billing.planSince && (
                        <p className="text-xs text-muted">
                            {t('billing.since', { date: formatDate(billing.planSince) })}
                        </p>
                    )}
                </section>
            )}
            <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4 sm:p-5">
                <h2 className="eyebrow text-muted">{t('billing.credit_history')}</h2>
                {billing.ledger.length === 0 ? (
                    <p className="text-sm text-muted">{t('billing.no_credit_history')}</p>
                ) : (
                    <ul className="flex flex-col divide-y divide-line">
                        {billing.ledger.map((entry) => (
                            <li
                                key={entry.id}
                                className="flex items-baseline justify-between gap-3 py-2 text-sm"
                            >
                                <span className="min-w-0">
                                    {t(`credit_reason.${entry.reason}`)}
                                    <span className="block text-xs text-muted">
                                        {[
                                            entry.job,
                                            entry.invoice,
                                            entry.note,
                                            formatDate(entry.at),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </span>
                                </span>
                                <span
                                    className={`font-semibold tnum ${entry.delta < 0 ? 'text-bad' : 'text-ok'}`}
                                >
                                    {entry.delta > 0 ? '+' : ''}
                                    {formatNumber(entry.delta)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </div>
    );
}
