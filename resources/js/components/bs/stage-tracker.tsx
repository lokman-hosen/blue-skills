import { Ban, Check, SkipForward } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export type StageStatus = 'done' | 'current' | 'pending' | 'blocked' | 'skipped';

export type Stage = {
    key: string;
    label: string;
    status: StageStatus;
    /** ISO date: when it was completed, or when it is due for the current stage. */
    date?: string | null;
};

type StageTrackerProps = {
    stages: Stage[];
    /** `timeline` for mobility cases; `steps` for the compact application tracker. */
    variant?: 'timeline' | 'steps';
    className?: string;
};

export function StageTracker({ stages, variant = 'timeline', className }: StageTrackerProps) {
    const { t, formatDate } = useTranslation();
    const done = stages.filter((stage) => stage.status === 'done').length;
    const summary = t('stage.progress', { done, total: stages.length });

    if (variant === 'steps') {
        return (
            <div className={cn('min-w-0', className)}>
                <p className="sr-only">{summary}</p>
                <ol className="flex flex-col gap-1.5 sm:flex-row sm:flex-wrap sm:gap-x-4">
                    {stages.map((stage) => (
                        <li
                            key={stage.key}
                            className={cn(
                                'flex items-center gap-1.5 text-[13px]',
                                stage.status === 'current' && 'font-bold text-stamp',
                                (stage.status === 'pending' || stage.status === 'skipped') &&
                                    'text-muted',
                            )}
                        >
                            <Dot status={stage.status} />
                            <span>{stage.label}</span>
                            <span className="sr-only">· {t(`stage.${stage.status}`)}</span>
                        </li>
                    ))}
                </ol>
            </div>
        );
    }

    return (
        <div className={cn('min-w-0', className)}>
            <p className="sr-only">{summary}</p>
            <ol className="relative ms-2 border-s-2 border-line">
                {stages.map((stage) => (
                    <li key={stage.key} className="relative ps-5 pb-3 last:pb-0">
                        <Dot status={stage.status} className="absolute -start-[9px] top-0.5" />
                        <div className="flex justify-between gap-2">
                            <span
                                className={cn(
                                    'text-[13.5px]',
                                    stage.status === 'current' && 'font-bold',
                                    (stage.status === 'pending' || stage.status === 'skipped') &&
                                        'text-muted',
                                    stage.status === 'skipped' && 'line-through',
                                    stage.status === 'blocked' && 'font-semibold text-bad',
                                )}
                            >
                                {stage.label}
                                <span className="sr-only"> · {t(`stage.${stage.status}`)}</span>
                            </span>
                            {stage.date && (
                                <span
                                    className={cn(
                                        'shrink-0 text-[11.5px]',
                                        stage.status === 'current'
                                            ? 'font-semibold text-stamp'
                                            : 'text-muted',
                                    )}
                                >
                                    {stage.status === 'current'
                                        ? t('stage.due', {
                                              date: formatDate(stage.date, {
                                                  day: 'numeric',
                                                  month: 'short',
                                              }),
                                          })
                                        : formatDate(stage.date, {
                                              day: 'numeric',
                                              month: 'short',
                                          })}
                                </span>
                            )}
                        </div>
                    </li>
                ))}
            </ol>
        </div>
    );
}

function Dot({ status, className }: { status: StageStatus; className?: string }) {
    return (
        <span
            aria-hidden
            className={cn(
                'grid size-4 shrink-0 place-items-center rounded-full',
                status === 'done' && 'bg-ok text-on-brand',
                status === 'current' && 'bg-stamp ring-4 ring-stamp-soft',
                status === 'pending' && 'border-2 border-line bg-surface',
                status === 'blocked' && 'bg-bad text-on-brand',
                status === 'skipped' && 'border-2 border-line bg-surface-2 text-muted',
                className,
            )}
        >
            {status === 'done' && <Check className="size-2.5" strokeWidth={3} />}
            {status === 'blocked' && <Ban className="size-2.5" strokeWidth={3} />}
            {status === 'skipped' && <SkipForward className="size-2" strokeWidth={3} />}
        </span>
    );
}
