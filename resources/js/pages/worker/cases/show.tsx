import { Head, Link } from '@inertiajs/react';
import { CalendarClock, CircleAlert, MessageSquare, ShieldAlert } from 'lucide-react';
import { CaseHistory, trackerStages } from '@/components/bs/mobility/case-history';
import { PageHeader } from '@/components/bs/page-header';
import { StageTracker } from '@/components/bs/stage-tracker';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import worker from '@/routes/worker';

type Props = {
    mobilityCase: App.Domain.Mobility.Data.MobilityCaseData;
};

/** The worker's journey after accepting an offer (prototype w-case, spec §6). */
export default function WorkerCaseShow({ mobilityCase }: Props) {
    const { t, formatDate } = useTranslation();
    const current = mobilityCase.current;
    const passportStage = mobilityCase.stages.some((stage) => stage.key === 'passport_submitted');

    return (
        <>
            <Head title={t('mobility.journey_title', { country: mobilityCase.destination })} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    eyebrow={t('mobility.case_reference', { reference: mobilityCase.reference })}
                    title={t('mobility.journey_title', { country: mobilityCase.destination })}
                    description={[mobilityCase.jobTitle, mobilityCase.employer].join(' · ')}
                    back={{
                        href: worker.applications.show(mobilityCase.applicationId),
                        label: mobilityCase.jobTitle,
                    }}
                    actions={<StatusChip status={mobilityCase.status} />}
                />

                <section className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5">
                    <div className="rounded-xl bg-surface-2 p-3 text-[13px]">
                        <p>
                            <span className="text-muted">{t('mobility.handled_by')} </span>
                            <span className="font-semibold">{mobilityCase.handledBy}</span>
                        </p>
                        {mobilityCase.closedAt && mobilityCase.closedReason && (
                            <p className="mt-1">
                                {t('mobility.closed_on', {
                                    date: formatDate(mobilityCase.closedAt),
                                    reason: t(
                                        `mobility.closed_reason.${mobilityCase.closedReason}`,
                                    ),
                                })}
                            </p>
                        )}
                    </div>

                    {current && (
                        <div
                            className="flex flex-col gap-2 rounded-xl border-2 border-stamp/40 bg-stamp-soft p-4"
                            aria-live="polite"
                        >
                            <p className="eyebrow text-muted">{t('mobility.next_action')}</p>
                            <p className="font-semibold">{current.label}</p>
                            {current.status === 'blocked' && current.blockedReason && (
                                <p className="flex items-start gap-1.5 text-sm text-bad">
                                    <CircleAlert aria-hidden className="mt-0.5 size-4 shrink-0" />
                                    {t('mobility.on_hold', { reason: current.blockedReason })}
                                </p>
                            )}
                            <p className="text-sm whitespace-pre-line">
                                {current.nextAction ?? t('mobility.no_next_action')}
                            </p>
                            {current.dueOn && (
                                <p className="flex items-center gap-1.5 text-sm font-semibold text-stamp">
                                    <CalendarClock aria-hidden className="size-4" />
                                    {t('mobility.due', { date: formatDate(current.dueOn) })}
                                </p>
                            )}
                        </div>
                    )}

                    <StageTracker stages={trackerStages(mobilityCase.stages)} />

                    {passportStage && (
                        <p className="flex items-start gap-2 rounded-lg bg-surface-2 px-3 py-2 text-xs">
                            <ShieldAlert
                                aria-hidden
                                className="mt-0.5 size-4 shrink-0 text-brand"
                            />
                            {t('mobility.passport_note')}
                        </p>
                    )}

                    <Button asChild variant="outline" className="min-h-11 self-start">
                        <Link href={worker.applications.messages(mobilityCase.applicationId)}>
                            <MessageSquare aria-hidden className="size-4" />
                            {t('messages.message_employer')}
                        </Link>
                    </Button>
                </section>

                <CaseHistory events={mobilityCase.events} />
            </div>
        </>
    );
}
