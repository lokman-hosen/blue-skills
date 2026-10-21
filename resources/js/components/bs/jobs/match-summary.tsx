import { Check, X } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * "X of Y requirements met" with each requirement listed (product rule 12). A guide from the
 * worker's confirmed profile, not a decision.
 */
export function MatchSummary({
    match,
    note,
    className,
}: {
    match: App.Domain.Jobs.Data.MatchSummaryData;
    /** Explains where the match comes from; defaults to the worker's own wording. */
    note?: string;
    className?: string;
}) {
    const { t, formatNumber } = useTranslation();

    return (
        <details className={cn('rounded-xl border border-line bg-surface p-4', className)} open>
            <summary className="min-h-11 cursor-pointer content-center font-semibold">
                {t('jobs.match.summary', {
                    met: formatNumber(match.met),
                    total: formatNumber(match.total),
                })}
            </summary>
            <ul className="mt-2 flex flex-col gap-1.5">
                {match.requirements.map((requirement, index) => (
                    <li key={index} className="flex items-start gap-2 text-sm">
                        {requirement.met ? (
                            <Check aria-hidden className="mt-0.5 size-4 shrink-0 text-ok" />
                        ) : (
                            <X aria-hidden className="mt-0.5 size-4 shrink-0 text-bad" />
                        )}
                        <span>
                            {t(`jobs.match.requirement.${requirement.key}`, requirement.params)}
                            <span className="sr-only">
                                {' '}
                                · {requirement.met ? t('jobs.match.met') : t('jobs.match.not_met')}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>
            <p className="mt-3 text-xs text-muted">{note ?? t('jobs.match.note')}</p>
        </details>
    );
}
