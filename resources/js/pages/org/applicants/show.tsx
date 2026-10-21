import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarClock, FileLock2, Handshake, MessageSquare, Phone } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { MatchSummary } from '@/components/bs/jobs/match-summary';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { SkillShowPlayer } from '@/components/bs/skill-show-player';
import { StageTracker } from '@/components/bs/stage-tracker';
import type { StageStatus } from '@/components/bs/stage-tracker';
import { StatusChip } from '@/components/bs/status-chip';
import { WorkerProjection } from '@/components/bs/worker-projection';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Applicant = App.Domain.Applications.Data.ApplicantData;

type Props = {
    applicant: Applicant;
    abilities: { manage: boolean; interview: boolean };
    members: App.Domain.Applications.Data.MemberOptionData[];
    rejectionReasons: App.Domain.Applications.Enums.RejectionReason[];
    interviewModes: App.Domain.Applications.Enums.InterviewMode[];
    interviewOutcomes: App.Domain.Applications.Enums.InterviewOutcome[];
};

/**
 * One applicant as the organization may see them: the dated snapshot, items the worker still
 * shares, history, interviews and offers (spec §6, product rules 1, 6 and 7).
 */
export default function ApplicantShow({
    applicant,
    abilities,
    members,
    rejectionReasons,
    interviewModes,
    interviewOutcomes,
}: Props) {
    const { t, formatDate, formatMoney } = useTranslation();
    const snapshot = applicant.snapshot;
    const name = snapshot?.name ?? '';

    return (
        <>
            <Head title={name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={`${applicant.jobCode} · ${snapshot?.reference ?? ''}`}
                    title={name}
                    description={applicant.jobTitle}
                    back={{
                        href: org.jobs.applicants(applicant.jobCode),
                        label: t('applicants.back'),
                    }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={applicant.status} />
                            <Button asChild variant="outline" className="min-h-11">
                                <Link href={org.applicants.messages(applicant.id)}>
                                    <MessageSquare aria-hidden className="size-4" />
                                    {t('messages.message_worker')}
                                </Link>
                            </Button>
                        </div>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,360px)] lg:items-start">
                    <div className="flex min-w-0 flex-col gap-5">
                        <Panel title={t('applicants.profile')}>
                            {applicant.snapshotTakenAt && (
                                <p className="mb-3 text-xs text-muted">
                                    {t('applicants.snapshot_note', {
                                        date: formatDate(applicant.snapshotTakenAt),
                                    })}
                                </p>
                            )}
                            {snapshot ? (
                                <WorkerProjection profile={snapshot} />
                            ) : (
                                <p className="text-sm text-muted">{t('common.not_disclosed')}</p>
                            )}
                        </Panel>

                        {snapshot && (
                            <MatchSummary
                                match={snapshot.match}
                                note={t('applicants.match_note')}
                            />
                        )}

                        {applicant.skillShow && (
                            <Panel title={t('nav.skillshow')}>
                                <SkillShowPlayer
                                    skillShow={applicant.skillShow}
                                    className="max-w-xs"
                                />
                            </Panel>
                        )}

                        <Panel title={t('applicants.contact')}>
                            <ul className="flex flex-col gap-2 text-sm">
                                <li className="flex items-center gap-2">
                                    <Phone aria-hidden className="size-4 text-muted" />
                                    {applicant.phone ? (
                                        <a
                                            href={`tel:${applicant.phone}`}
                                            className="font-semibold text-brand"
                                            dir="ltr"
                                        >
                                            {applicant.phone}
                                        </a>
                                    ) : (
                                        <span className="text-muted">
                                            {t('applicants.phone_hidden')}
                                        </span>
                                    )}
                                </li>
                                <li className="flex items-center gap-2">
                                    <FileLock2 aria-hidden className="size-4 text-muted" />
                                    {applicant.passportScanUrl ? (
                                        <a
                                            href={applicant.passportScanUrl}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-semibold text-brand"
                                        >
                                            {t('applicants.open_scan')}
                                        </a>
                                    ) : (
                                        <span className="text-muted">
                                            {t('applicants.scan_hidden')}
                                        </span>
                                    )}
                                </li>
                            </ul>
                        </Panel>

                        <Panel title={t('applicants.history')}>
                            <ol className="flex flex-col gap-2 text-sm">
                                {applicant.events.map((event) => (
                                    <li key={event.id} className="flex flex-col">
                                        <span className="font-semibold">
                                            {t(`status.${event.to}`)}
                                            {event.reasonCode &&
                                                ` · ${t(`rejection_reason.${event.reasonCode}`)}`}
                                        </span>
                                        <span className="text-xs text-muted">
                                            {formatDate(event.at, {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            })}
                                            {event.actorName && ` · ${event.actorName}`}
                                        </span>
                                        {event.note && (
                                            <span className="text-xs whitespace-pre-line">
                                                {event.note}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        </Panel>
                    </div>

                    <aside className="flex flex-col gap-4 lg:sticky lg:top-20">
                        <Panel title={t('applicants.stage')}>
                            <StageTracker
                                variant="steps"
                                stages={applicant.stages.map((stage) => ({
                                    key: stage.key,
                                    label: t(`status.${stage.key}`),
                                    status: stage.status as StageStatus,
                                }))}
                            />
                            {abilities.manage && !applicant.closed && (
                                <div className="mt-4 flex flex-col gap-2">
                                    {applicant.nextStages.length > 0 && (
                                        <MoveForm applicant={applicant} />
                                    )}
                                    {applicant.canOffer && <OfferDialog applicant={applicant} />}
                                    <RejectDialog
                                        applicant={applicant}
                                        reasons={rejectionReasons}
                                    />
                                </div>
                            )}
                        </Panel>

                        {abilities.manage && <AssignForm applicant={applicant} members={members} />}

                        <Panel title={t('applications.interview.title')}>
                            {applicant.interviews.length === 0 ? (
                                <p className="text-sm text-muted">
                                    {t('applicants.no_interviews')}
                                </p>
                            ) : (
                                <ul className="flex flex-col gap-3">
                                    {applicant.interviews.map((interview) => (
                                        <li
                                            key={interview.id}
                                            className="flex flex-col gap-1 rounded-lg border border-line p-3 text-sm"
                                        >
                                            <span className="flex items-center gap-1.5 font-semibold">
                                                <CalendarClock
                                                    aria-hidden
                                                    className="size-4 text-brand"
                                                />
                                                {formatDate(interview.scheduledAt, {
                                                    dateStyle: 'medium',
                                                    timeStyle: 'short',
                                                })}
                                            </span>
                                            <span className="text-xs text-muted">
                                                {t(`interview_mode.${interview.mode}`)}
                                                {interview.location && ` · ${interview.location}`}
                                            </span>
                                            {interview.outcome ? (
                                                <StatusChip
                                                    className="self-start"
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
                                                abilities.interview && (
                                                    <OutcomeForm
                                                        interview={interview}
                                                        outcomes={interviewOutcomes}
                                                    />
                                                )
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {abilities.interview && !applicant.closed && (
                                <InterviewDialog applicant={applicant} modes={interviewModes} />
                            )}
                        </Panel>

                        {applicant.offers.length > 0 && (
                            <Panel title={t('applicants.offers')}>
                                <ul className="flex flex-col gap-2 text-sm">
                                    {applicant.offers.map((offer) => (
                                        <li
                                            key={offer.id}
                                            className="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <span>
                                                <span className="font-semibold">
                                                    {formatMoney(offer.salary)} /{' '}
                                                    {t(`salary_period.${offer.salaryPeriod}`)}
                                                </span>
                                                <span className="block text-xs text-muted">
                                                    {formatDate(offer.sentAt)}
                                                </span>
                                            </span>
                                            <StatusChip
                                                label={t(`offer_status.${offer.status}`)}
                                                tone={
                                                    offer.status === 'accepted'
                                                        ? 'ok'
                                                        : offer.status === 'sent'
                                                          ? 'stamp'
                                                          : 'neutral'
                                                }
                                            />
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        )}

                        <Panel title={t('applicants.shared_title')}>
                            <ul className="flex flex-col gap-1.5 text-sm">
                                {applicant.consents.map((consent) => (
                                    <li
                                        key={consent.item}
                                        className="flex items-center justify-between gap-2"
                                    >
                                        {t(`consent.item.${consent.item}`)}
                                        <StatusChip
                                            icon={false}
                                            label={
                                                consent.active
                                                    ? t('consent.shared')
                                                    : consent.revokedAt
                                                      ? t('applicants.revoked')
                                                      : t('consent.not_shared')
                                            }
                                            tone={consent.active ? 'ok' : 'neutral'}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </Panel>
                    </aside>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function MoveForm({ applicant }: { applicant: Applicant }) {
    const { t } = useTranslation();
    const [stage, setStage] = useState(applicant.nextStages[0]);
    const [processing, setProcessing] = useState(false);

    return (
        <div className="flex flex-wrap items-end gap-2">
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <Label htmlFor="move-stage" className="text-xs">
                    {t('applicants.move_to')}
                </Label>
                <NativeSelect
                    id="move-stage"
                    value={stage}
                    onChange={(event) => setStage(event.target.value)}
                >
                    {applicant.nextStages.map((value) => (
                        <option key={value} value={value}>
                            {t(`status.${value}`)}
                        </option>
                    ))}
                </NativeSelect>
            </div>
            <Button
                className="min-h-11"
                disabled={processing}
                onClick={() =>
                    router.post(
                        org.applicants.move.url(),
                        { application_ids: [applicant.id], stage },
                        {
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                        },
                    )
                }
            >
                {processing && <Spinner />}
                {t('applicants.move')}
            </Button>
        </div>
    );
}

function AssignForm({
    applicant,
    members,
}: {
    applicant: Applicant;
    members: App.Domain.Applications.Data.MemberOptionData[];
}) {
    const { t } = useTranslation();
    const form = useForm({ assigned_user_id: applicant.assignee?.id ?? '' });

    return (
        <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4">
            <Label htmlFor="assignee" className="eyebrow text-muted">
                {t('applicants.handled_by')}
            </Label>
            <div className="flex flex-wrap items-end gap-2">
                <NativeSelect
                    id="assignee"
                    className="min-w-0 flex-1"
                    value={form.data.assigned_user_id}
                    onChange={(event) => form.setData('assigned_user_id', event.target.value)}
                >
                    <option value="">{t('applicants.unassigned')}</option>
                    {members.map((member) => (
                        <option key={member.id} value={member.id}>
                            {member.name}
                        </option>
                    ))}
                </NativeSelect>
                <Button
                    variant="outline"
                    className="min-h-11"
                    disabled={
                        form.processing ||
                        form.data.assigned_user_id === (applicant.assignee?.id ?? '')
                    }
                    onClick={() =>
                        form.put(org.applicants.assign.url(applicant.id), { preserveScroll: true })
                    }
                >
                    {t('common.save')}
                </Button>
            </div>
            <InputError message={form.errors.assigned_user_id} />
        </section>
    );
}

function ActionDialog({
    trigger,
    title,
    description,
    children,
    open,
    onOpenChange,
}: {
    trigger: ReactNode;
    title: string;
    description: string;
    children: ReactNode;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}

function RejectDialog({
    applicant,
    reasons,
}: {
    applicant: Applicant;
    reasons: App.Domain.Applications.Enums.RejectionReason[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ reason: '', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.applicants.reject.url(applicant.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <ActionDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <Button variant="outline" className="min-h-11 text-bad">
                    {t('applicants.reject')}
                </Button>
            }
            title={t('applicants.reject_title')}
            description={t('applicants.reject_body')}
        >
            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="reject-reason">{t('applicants.reason')}</Label>
                    <NativeSelect
                        id="reject-reason"
                        value={form.data.reason}
                        onChange={(event) => form.setData('reason', event.target.value)}
                    >
                        <option value="">{t('common.choose')}</option>
                        {reasons.map((reason) => (
                            <option key={reason} value={reason}>
                                {t(`rejection_reason.${reason}`)}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.reason} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="reject-note">
                        {t('applicants.internal_note')} ({t('common.optional')})
                    </Label>
                    <textarea
                        rows={3}
                        className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        id="reject-note"
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        maxLength={1000}
                    />
                    <p className="text-xs text-muted">{t('applicants.internal_note_hint')}</p>
                    <InputError message={form.errors.note} />
                </div>
                <Button
                    type="submit"
                    variant="destructive"
                    className="min-h-11"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('applicants.reject')}
                </Button>
            </form>
        </ActionDialog>
    );
}

function InterviewDialog({
    applicant,
    modes,
}: {
    applicant: Applicant;
    modes: App.Domain.Applications.Enums.InterviewMode[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        scheduled_at: '',
        duration_minutes: 30,
        mode: modes[0] ?? 'video',
        location: '',
        instructions: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.applicants.interviews.store.url(applicant.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <ActionDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <Button variant="outline" className="mt-2 min-h-11">
                    <CalendarClock aria-hidden className="size-4" />
                    {t('applicants.schedule')}
                </Button>
            }
            title={t('applicants.schedule')}
            description={t('applicants.schedule_body')}
        >
            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="interview-at">{t('applicants.when')}</Label>
                        <Input
                            id="interview-at"
                            type="datetime-local"
                            className="h-11"
                            value={form.data.scheduled_at}
                            onChange={(event) => form.setData('scheduled_at', event.target.value)}
                        />
                        <InputError message={form.errors.scheduled_at} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="interview-length">{t('applicants.length')}</Label>
                        <Input
                            id="interview-length"
                            type="number"
                            inputMode="numeric"
                            min={10}
                            max={240}
                            className="h-11"
                            value={form.data.duration_minutes}
                            onChange={(event) =>
                                form.setData('duration_minutes', Number(event.target.value))
                            }
                        />
                        <InputError message={form.errors.duration_minutes} />
                    </div>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="interview-mode">{t('applicants.mode')}</Label>
                    <NativeSelect
                        id="interview-mode"
                        value={form.data.mode}
                        onChange={(event) =>
                            form.setData(
                                'mode',
                                event.target.value as App.Domain.Applications.Enums.InterviewMode,
                            )
                        }
                    >
                        {modes.map((mode) => (
                            <option key={mode} value={mode}>
                                {t(`interview_mode.${mode}`)}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="interview-location">
                        {form.data.mode === 'in_person'
                            ? t('applicants.address')
                            : `${t('applicants.link')} (${t('common.optional')})`}
                    </Label>
                    <Input
                        id="interview-location"
                        className="h-11"
                        value={form.data.location}
                        onChange={(event) => form.setData('location', event.target.value)}
                        maxLength={255}
                    />
                    <InputError message={form.errors.location} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="interview-instructions">
                        {t('applicants.instructions')} ({t('common.optional')})
                    </Label>
                    <textarea
                        rows={3}
                        className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        id="interview-instructions"
                        value={form.data.instructions}
                        onChange={(event) => form.setData('instructions', event.target.value)}
                        maxLength={1000}
                    />
                    <InputError message={form.errors.instructions} />
                </div>
                <p className="text-xs text-muted">{t('applicants.time_zone_note')}</p>
                <Button type="submit" className="min-h-11" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    {t('applicants.schedule')}
                </Button>
            </form>
        </ActionDialog>
    );
}

function OutcomeForm({
    interview,
    outcomes,
}: {
    interview: App.Domain.Applications.Data.InterviewData;
    outcomes: App.Domain.Applications.Enums.InterviewOutcome[];
}) {
    const { t } = useTranslation();
    const form = useForm({ outcome: '', note: '' });

    return (
        <div className="mt-1 flex flex-wrap items-end gap-2">
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <Label htmlFor={`outcome-${interview.id}`} className="text-xs">
                    {t('applicants.outcome')}
                </Label>
                <NativeSelect
                    id={`outcome-${interview.id}`}
                    value={form.data.outcome}
                    onChange={(event) => form.setData('outcome', event.target.value)}
                >
                    <option value="">{t('common.choose')}</option>
                    {outcomes.map((outcome) => (
                        <option key={outcome} value={outcome}>
                            {t(`interview_outcome.${outcome}`)}
                        </option>
                    ))}
                </NativeSelect>
            </div>
            <Button
                variant="outline"
                className="min-h-11"
                disabled={form.processing || form.data.outcome === ''}
                onClick={() =>
                    form.put(org.interviews.update.url(interview.id), { preserveScroll: true })
                }
            >
                {t('common.save')}
            </Button>
            <InputError message={form.errors.outcome} className="w-full" />
        </div>
    );
}

function OfferDialog({ applicant }: { applicant: Applicant }) {
    const { t, formatMoney } = useTranslation();
    const [open, setOpen] = useState(false);
    const currencyDigits = applicant.jobSalary
        ? (new Intl.NumberFormat('en', {
              style: 'currency',
              currency: applicant.jobSalary.currency,
          }).resolvedOptions().maximumFractionDigits ?? 2)
        : 0;
    const form = useForm({
        salary_amount: applicant.jobSalary
            ? String(Math.round(applicant.jobSalary.amountMinor / 10 ** currencyDigits))
            : '',
        start_on: '',
        respond_by: '',
        note: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.applicants.offers.store.url(applicant.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <ActionDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <Button className="min-h-11">
                    <Handshake aria-hidden className="size-4" />
                    {t('applicants.offer')}
                </Button>
            }
            title={t('applicants.offer')}
            description={
                applicant.jobSalary
                    ? t('applicants.offer_body', {
                          salary: formatMoney(applicant.jobSalary),
                          period: applicant.jobSalaryPeriod
                              ? t(`salary_period.${applicant.jobSalaryPeriod}`)
                              : '',
                      })
                    : t('applications.offer.job_salary_missing')
            }
        >
            <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="offer-salary">
                        {t('jobs.field.salary')} ({applicant.jobSalary?.currency}
                        {applicant.jobSalaryPeriod &&
                            ` / ${t(`salary_period.${applicant.jobSalaryPeriod}`)}`}
                        )
                    </Label>
                    <Input
                        id="offer-salary"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        className="h-11"
                        value={form.data.salary_amount}
                        onChange={(event) => form.setData('salary_amount', event.target.value)}
                    />
                    <InputError message={form.errors.salary_amount} />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="offer-start">
                            {t('applications.offer.start_on')} ({t('common.optional')})
                        </Label>
                        <Input
                            id="offer-start"
                            type="date"
                            className="h-11"
                            value={form.data.start_on}
                            onChange={(event) => form.setData('start_on', event.target.value)}
                        />
                        <InputError message={form.errors.start_on} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="offer-respond">
                            {t('applications.offer.respond_by')} ({t('common.optional')})
                        </Label>
                        <Input
                            id="offer-respond"
                            type="date"
                            className="h-11"
                            value={form.data.respond_by}
                            onChange={(event) => form.setData('respond_by', event.target.value)}
                        />
                        <InputError message={form.errors.respond_by} />
                    </div>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="offer-note">
                        {t('applicants.offer_note')} ({t('common.optional')})
                    </Label>
                    <textarea
                        rows={3}
                        className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        id="offer-note"
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        maxLength={1000}
                    />
                    <InputError message={form.errors.note} />
                </div>
                <Button type="submit" className="min-h-11" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    {t('applicants.send_offer')}
                </Button>
            </form>
        </ActionDialog>
    );
}
