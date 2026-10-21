import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, CircleAlert, Handshake, MapPin, Video, Phone, Users } from 'lucide-react';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { PageHeader } from '@/components/bs/page-header';
import { StageTracker } from '@/components/bs/stage-tracker';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import jobs from '@/routes/jobs';
import worker from '@/routes/worker';

type Props = {
    application: App.Domain.Applications.Data.WorkerApplicationData;
};

const MODE_ICONS = { in_person: Users, video: Video, phone: Phone } as const;

/** One application: stages, next step, interviews, offers and shared items (spec §6). */
export default function ApplicationShow({ application }: Props) {
    const { t, formatDate, formatMoney } = useTranslation();
    const post = (url: string) => router.post(url, {}, { preserveScroll: true });
    const openOffer = application.offers.find((offer) => offer.status === 'sent');

    return (
        <>
            <Head title={application.job.title} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    eyebrow={application.job.code}
                    title={application.job.title}
                    description={[application.job.employerName, application.job.destinationCity]
                        .filter(Boolean)
                        .join(' · ')}
                    back={{ href: worker.applications.index(), label: t('nav.applications') }}
                    actions={<StatusChip status={application.status} />}
                />

                {application.jobChangedAt && (
                    <section
                        role="alert"
                        className="flex flex-col gap-3 rounded-xl border border-warn/40 bg-warn-soft p-4 text-sm"
                    >
                        <p className="flex items-start gap-2 font-semibold">
                            <CircleAlert aria-hidden className="mt-0.5 size-4 shrink-0 text-warn" />
                            {t('applications.job_changed.title', {
                                date: formatDate(application.jobChangedAt),
                            })}
                        </p>
                        <p>{t('applications.job_changed.body')}</p>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline" className="min-h-11">
                                <Link href={jobs.show(application.job.code)}>
                                    {t('applications.job_changed.view')}
                                </Link>
                            </Button>
                            <Button
                                className="min-h-11"
                                onClick={() =>
                                    post(worker.applications.jobChangeSeen.url(application.id))
                                }
                            >
                                {t('applications.job_changed.seen')}
                            </Button>
                        </div>
                    </section>
                )}

                <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5">
                    <div className="rounded-xl bg-surface-2 p-3 text-[13px]">
                        <p>
                            <span className="text-muted">{t('applications.handled_by')} </span>
                            <span className="font-semibold">{application.handledBy}</span>
                        </p>
                        <p className="mt-1">
                            <span className="text-muted">{t('applications.next.label')} </span>
                            {t(`applications.next.${application.nextStep}`)}
                        </p>
                    </div>
                    <StageTracker
                        stages={application.stages.map((stage) => ({
                            key: stage.key,
                            label: t(`status.${stage.key}`),
                            status: stage.status as
                                'done' | 'current' | 'pending' | 'blocked' | 'skipped',
                            date: stage.status === 'current' ? null : stage.date,
                        }))}
                    />
                    {application.status.value === 'rejected' && (
                        <RejectionNote events={application.events} />
                    )}
                    {application.status.value === 'accepted' && (
                        <p className="rounded-lg bg-ok-soft px-3 py-2 text-sm">
                            {t('applications.accepted_note')}
                        </p>
                    )}
                </section>

                {openOffer && (
                    <section
                        aria-labelledby="offer-heading"
                        className="flex flex-col gap-3 rounded-2xl border-2 border-brand bg-surface p-5"
                    >
                        <h2 id="offer-heading" className="flex items-center gap-2 font-semibold">
                            <Handshake aria-hidden className="size-5 text-brand" />
                            {t('applications.offer.title')}
                        </h2>
                        <dl className="grid gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-xs text-muted">{t('jobs.field.salary')}</dt>
                                <dd className="font-semibold">
                                    {formatMoney(openOffer.salary)} /{' '}
                                    {t(`salary_period.${openOffer.salaryPeriod}`)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted">
                                    {t('applications.offer.start_on')}
                                </dt>
                                <dd className="font-semibold">
                                    {openOffer.startOn
                                        ? formatDate(openOffer.startOn)
                                        : t('common.not_disclosed')}
                                </dd>
                            </div>
                            {openOffer.respondBy && (
                                <div>
                                    <dt className="text-xs text-muted">
                                        {t('applications.offer.respond_by')}
                                    </dt>
                                    <dd className="font-semibold">
                                        {formatDate(openOffer.respondBy)}
                                    </dd>
                                </div>
                            )}
                        </dl>
                        {openOffer.note && (
                            <p className="rounded-lg bg-surface-2 px-3 py-2 text-sm whitespace-pre-line">
                                {openOffer.note}
                            </p>
                        )}
                        <p className="text-xs text-muted">
                            {t('applications.offer.truth_card_note')}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <ConfirmDialog
                                trigger={
                                    <Button className="min-h-11">
                                        {t('applications.offer.accept')}
                                    </Button>
                                }
                                title={t('applications.offer.accept_title')}
                                description={t('applications.offer.accept_body')}
                                confirmLabel={t('applications.offer.accept')}
                                onConfirm={() =>
                                    post(worker.applications.offer.url([application.id, 'accept']))
                                }
                            />
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline" className="min-h-11">
                                        {t('applications.offer.decline')}
                                    </Button>
                                }
                                title={t('applications.offer.decline_title')}
                                description={t('applications.offer.decline_body')}
                                confirmLabel={t('applications.offer.decline')}
                                destructive
                                onConfirm={() =>
                                    post(worker.applications.offer.url([application.id, 'decline']))
                                }
                            />
                        </div>
                    </section>
                )}

                {application.interviews.length > 0 && (
                    <section
                        aria-labelledby="interviews-heading"
                        className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
                    >
                        <h2 id="interviews-heading" className="font-semibold">
                            {t('applications.interview.title')}
                        </h2>
                        <ul className="flex flex-col gap-3">
                            {application.interviews.map((interview) => {
                                const Icon = MODE_ICONS[interview.mode];

                                return (
                                    <li
                                        key={interview.id}
                                        className="flex flex-col gap-1 rounded-xl border border-line p-3 text-sm"
                                    >
                                        <p className="flex flex-wrap items-center justify-between gap-2 font-semibold">
                                            <span className="flex items-center gap-1.5">
                                                <CalendarClock
                                                    aria-hidden
                                                    className="size-4 text-brand"
                                                />
                                                {formatDate(interview.scheduledAt, {
                                                    dateStyle: 'full',
                                                    timeStyle: 'short',
                                                })}
                                            </span>
                                            {interview.outcome ? (
                                                <StatusChip
                                                    label={t(
                                                        `interview_outcome.${interview.outcome}`,
                                                    )}
                                                    tone={
                                                        interview.outcome === 'passed'
                                                            ? 'ok'
                                                            : 'neutral'
                                                    }
                                                />
                                            ) : (
                                                interview.upcoming && (
                                                    <StatusChip
                                                        label={t('applications.interview.upcoming')}
                                                        tone="stamp"
                                                    />
                                                )
                                            )}
                                        </p>
                                        <p className="flex items-center gap-1.5 text-muted">
                                            <Icon aria-hidden className="size-4" />
                                            {t(`interview_mode.${interview.mode}`)} ·{' '}
                                            {t('applications.interview.minutes', {
                                                minutes: interview.durationMinutes,
                                            })}
                                        </p>
                                        {interview.location && (
                                            <p className="flex items-start gap-1.5 break-words">
                                                <MapPin
                                                    aria-hidden
                                                    className="mt-0.5 size-4 shrink-0 text-muted"
                                                />
                                                {interview.location}
                                            </p>
                                        )}
                                        {interview.instructions && (
                                            <p className="whitespace-pre-line">
                                                {interview.instructions}
                                            </p>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </section>
                )}

                <section
                    aria-labelledby="shared-heading"
                    className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
                >
                    <h2 id="shared-heading" className="font-semibold">
                        {t('applications.shared.title')}
                    </h2>
                    {application.snapshotTakenAt && (
                        <p className="text-xs text-muted">
                            {t('applications.shared.snapshot', {
                                date: formatDate(application.snapshotTakenAt),
                            })}
                        </p>
                    )}
                    <ul className="flex flex-col divide-y divide-line">
                        {application.consents.map((consent) => (
                            <li
                                key={consent.item}
                                className="flex flex-wrap items-center justify-between gap-2 py-2.5"
                            >
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold">
                                        {t(`consent.item.${consent.item}`)}
                                    </p>
                                    {consent.revokedAt && (
                                        <p className="text-xs text-muted">
                                            {t('privacy.revoked_on', {
                                                date: formatDate(consent.revokedAt),
                                            })}
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    <StatusChip
                                        label={
                                            consent.active
                                                ? t('consent.shared')
                                                : t('consent.not_shared')
                                        }
                                        tone={consent.active ? 'ok' : 'neutral'}
                                        icon={false}
                                    />
                                    {consent.revocable && (
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="min-h-11"
                                                >
                                                    {t('applications.shared.stop')}
                                                </Button>
                                            }
                                            title={t('applications.shared.stop_title', {
                                                item: t(`consent.item.${consent.item}`),
                                            })}
                                            description={t('applications.shared.stop_body')}
                                            confirmLabel={t('applications.shared.stop')}
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    worker.applications.items.destroy.url([
                                                        application.id,
                                                        consent.item,
                                                    ]),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        />
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>

                {!application.closed && (
                    <ConfirmDialog
                        trigger={
                            <Button variant="outline" className="min-h-11 self-start text-bad">
                                {t('applications.withdraw')}
                            </Button>
                        }
                        title={t('applications.withdraw_title')}
                        description={t('applications.withdraw_body')}
                        confirmLabel={t('applications.withdraw')}
                        destructive
                        onConfirm={() => post(worker.applications.withdraw.url(application.id))}
                    />
                )}
            </div>
        </>
    );
}

function RejectionNote({ events }: { events: App.Domain.Applications.Data.StageEventData[] }) {
    const { t } = useTranslation();
    const rejection = [...events].reverse().find((event) => event.to === 'rejected');

    return (
        <p className="rounded-lg bg-surface-2 px-3 py-2 text-sm">
            {rejection?.reasonCode
                ? t('applications.rejected_reason', {
                      reason: t(`rejection_reason.${rejection.reasonCode}`),
                  })
                : t('applications.rejected_note')}
        </p>
    );
}
