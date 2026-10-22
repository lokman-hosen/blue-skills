import { useTranslation } from '@/lib/i18n';

type Funnel = App.Domain.Crm.Data.FunnelData;

export const FUNNEL_STEPS = [
    'visitors',
    'leads',
    'registrations',
    'profilesComplete',
    'qualified',
    'applied',
    'selected',
    'deployed',
] as const satisfies readonly (keyof Funnel)[];

/**
 * A campaign funnel as labelled bars (spec §9). Each step shows its count and its share of the
 * step before, so a drop-off is visible without reading a chart.
 */
export function FunnelBars({ funnel }: { funnel: Funnel }) {
    const { t, formatNumber } = useTranslation();
    const max = Math.max(1, ...FUNNEL_STEPS.map((step) => funnel[step]));

    return (
        <ol className="flex flex-col gap-2.5">
            {FUNNEL_STEPS.map((step, index) => {
                const value = funnel[step];
                const previous = index > 0 ? funnel[FUNNEL_STEPS[index - 1]] : null;
                const ratio = previous !== null && previous > 0 ? (value / previous) * 100 : null;
                const share =
                    ratio === null
                        ? null
                        : ratio > 0 && ratio < 1
                          ? '<1'
                          : formatNumber(Math.round(ratio));

                return (
                    <li key={step} className="flex flex-col gap-1">
                        <span className="flex items-baseline justify-between gap-3 text-sm">
                            <span>{t(`marketing.step.${step}`)}</span>
                            <span className="tnum">
                                <span className="font-semibold">{formatNumber(value)}</span>
                                {share !== null && (
                                    <span className="ms-2 text-xs text-muted">
                                        {t('marketing.of_previous', { percent: share })}
                                    </span>
                                )}
                            </span>
                        </span>
                        <span aria-hidden className="h-2 overflow-hidden rounded-full bg-surface-2">
                            <span
                                className="block h-full rounded-full bg-brand"
                                style={{ width: `${(value / max) * 100}%` }}
                            />
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
