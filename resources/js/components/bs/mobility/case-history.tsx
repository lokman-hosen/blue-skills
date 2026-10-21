import { useTranslation } from '@/lib/i18n';

type Props = {
    events: App.Domain.Mobility.Data.CaseEventData[];
};

/** A case's history, newest first. Never edited (spec §6). */
export function CaseHistory({ events }: Props) {
    const { t, formatDate } = useTranslation();

    return (
        <section
            aria-labelledby="case-history-heading"
            className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
        >
            <h2 id="case-history-heading" className="font-semibold">
                {t('mobility.history')}
            </h2>
            <ul className="flex flex-col divide-y divide-line">
                {events.map((event) => (
                    <li key={event.id} className="flex flex-col gap-0.5 py-2.5 text-sm">
                        <p className="flex flex-wrap justify-between gap-x-3">
                            <span className="font-semibold">
                                {t(`mobility.event.${event.action}`, { stage: event.stage ?? '' })}
                            </span>
                            <time dateTime={event.at} className="text-xs text-muted">
                                {formatDate(event.at, { dateStyle: 'medium', timeStyle: 'short' })}
                            </time>
                        </p>
                        {event.actor && <p className="text-xs text-muted">{event.actor}</p>}
                        {event.note && (
                            <p className="break-words whitespace-pre-line">{event.note}</p>
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}

/** Tracker stages from case stages; the date shows when a stage finished or is due. */
export function trackerStages(stages: App.Domain.Mobility.Data.CaseStageData[]) {
    return stages.map((stage) => ({
        key: stage.key,
        label: stage.label,
        status: stage.status as 'done' | 'current' | 'pending' | 'blocked' | 'skipped',
        date: stage.date,
    }));
}
