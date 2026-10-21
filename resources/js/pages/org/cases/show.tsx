import { Head, Link, router, useForm } from '@inertiajs/react';
import { Ban, Check, CircleAlert, MessageSquare, Play, SkipForward } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { CaseHistory, trackerStages } from '@/components/bs/mobility/case-history';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StageTracker } from '@/components/bs/stage-tracker';
import { StatusChip } from '@/components/bs/status-chip';
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

type MobilityCase = App.Domain.Mobility.Data.MobilityCaseData;
type CaseStage = App.Domain.Mobility.Data.CaseStageData;

type Props = {
    mobilityCase: MobilityCase;
    closeReasons: App.Domain.Mobility.Enums.CaseClosedReason[];
    canManage: boolean;
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** One mobility case: the managing organization runs its stages (spec §6, product rule 8). */
export default function OrgCaseShow({ mobilityCase, closeReasons, canManage }: Props) {
    const { t, formatDate } = useTranslation();
    const current = mobilityCase.current;
    const open = mobilityCase.status.value === 'case_open';

    return (
        <>
            <Head title={mobilityCase.reference} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={`${mobilityCase.jobCode} · ${mobilityCase.reference}`}
                    title={mobilityCase.workerName ?? mobilityCase.reference}
                    description={`${mobilityCase.jobTitle} · ${mobilityCase.destination}`}
                    back={{
                        href: org.applicants.show(mobilityCase.applicationId),
                        label: t('mobility.case_title'),
                    }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={mobilityCase.status} />
                            <Button asChild variant="outline" className="min-h-11">
                                <Link href={org.applicants.messages(mobilityCase.applicationId)}>
                                    <MessageSquare aria-hidden className="size-4" />
                                    {t('messages.message_worker')}
                                </Link>
                            </Button>
                        </div>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
                    <div className="flex min-w-0 flex-col gap-5">
                        {current && canManage && (
                            <CurrentStagePanel mobilityCase={mobilityCase} stage={current} />
                        )}
                        {current && !canManage && (
                            <Panel title={t('mobility.current_stage')}>
                                <p className="font-semibold">{current.label}</p>
                                {current.blockedReason && (
                                    <p className="text-sm text-bad">
                                        {t('mobility.on_hold', { reason: current.blockedReason })}
                                    </p>
                                )}
                                {current.nextAction && (
                                    <p className="text-sm whitespace-pre-line">
                                        {current.nextAction}
                                    </p>
                                )}
                                <p className="text-xs text-muted">
                                    {t('mobility.view_only', { org: mobilityCase.managedBy })}
                                </p>
                            </Panel>
                        )}
                        <Panel title={t('mobility.stages')}>
                            <StageTracker stages={trackerStages(mobilityCase.stages)} />
                        </Panel>
                        <CaseHistory events={mobilityCase.events} />
                    </div>

                    <div className="flex min-w-0 flex-col gap-5">
                        <Panel title={t('mobility.case_title')}>
                            <dl className="grid gap-2 text-sm">
                                <Row label={t('mobility.worker')}>{mobilityCase.workerName}</Row>
                                <Row label={t('mobility.employer')}>{mobilityCase.employer}</Row>
                                {mobilityCase.agency && (
                                    <Row label={t('mobility.agency')}>{mobilityCase.agency}</Row>
                                )}
                                <Row label={t('mobility.handled_by')}>{mobilityCase.handledBy}</Row>
                                {mobilityCase.closedAt && mobilityCase.closedReason && (
                                    <Row label={t('mobility.close.title')}>
                                        {t('mobility.closed_on', {
                                            date: formatDate(mobilityCase.closedAt),
                                            reason: t(
                                                `mobility.closed_reason.${mobilityCase.closedReason}`,
                                            ),
                                        })}
                                    </Row>
                                )}
                            </dl>
                        </Panel>
                        {open && canManage && (
                            <CloseDialog mobilityCase={mobilityCase} reasons={closeReasons} />
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex flex-wrap justify-between gap-x-3">
            <dt className="text-muted">{label}</dt>
            <dd className="min-w-0 text-end font-semibold break-words">{children}</dd>
        </div>
    );
}

function CurrentStagePanel({
    mobilityCase,
    stage,
}: {
    mobilityCase: MobilityCase;
    stage: CaseStage;
}) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);
    const [stageError, setStageError] = useState<string | undefined>();
    const form = useForm({ next_action: stage.nextAction ?? '', due_on: stage.dueOn ?? '' });
    const blocked = stage.status === 'blocked';

    const change = (action: 'complete' | 'resume') =>
        router.post(
            org.cases.stages.change.url([mobilityCase.id, stage.id, action]),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setStageError(errors.stage),
            },
        );

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(org.cases.stages.update.url([mobilityCase.id, stage.id]), {
            preserveScroll: true,
        });
    };

    return (
        <section
            aria-labelledby="current-stage-heading"
            className="flex flex-col gap-4 rounded-2xl border-2 border-stamp/40 bg-surface p-4 sm:p-5"
        >
            <div className="flex flex-col gap-1">
                <p className="eyebrow text-muted">{t('mobility.current_stage')}</p>
                <h2 id="current-stage-heading" className="text-lg font-semibold">
                    {stage.label}
                </h2>
                {blocked && stage.blockedReason && (
                    <p className="flex items-start gap-1.5 text-sm text-bad">
                        <CircleAlert aria-hidden className="mt-0.5 size-4 shrink-0" />
                        {t('mobility.on_hold', { reason: stage.blockedReason })}
                    </p>
                )}
            </div>

            <div className="flex flex-wrap gap-2">
                {blocked ? (
                    <Button
                        className="min-h-11"
                        disabled={processing}
                        onClick={() => change('resume')}
                    >
                        {processing ? <Spinner /> : <Play aria-hidden className="size-4" />}
                        {t('mobility.action.resume')}
                    </Button>
                ) : (
                    <>
                        <Button
                            className="min-h-11"
                            disabled={processing}
                            onClick={() => change('complete')}
                        >
                            {processing ? <Spinner /> : <Check aria-hidden className="size-4" />}
                            {t('mobility.action.complete')}
                        </Button>
                        <NoteDialog mobilityCase={mobilityCase} stage={stage} action="block" />
                        <NoteDialog mobilityCase={mobilityCase} stage={stage} action="skip" />
                    </>
                )}
            </div>
            <InputError message={stageError} />

            <form
                onSubmit={save}
                className="flex flex-col gap-3 border-t border-line pt-4"
                noValidate
            >
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="next_action">{t('mobility.field.next_action')}</Label>
                    <textarea
                        id="next_action"
                        rows={3}
                        maxLength={500}
                        value={form.data.next_action}
                        onChange={(event) => form.setData('next_action', event.target.value)}
                        className={TEXTAREA}
                        aria-describedby="next_action_hint"
                    />
                    <p id="next_action_hint" className="text-xs text-muted">
                        {t('mobility.field.next_action_hint')}
                    </p>
                    <InputError message={form.errors.next_action} />
                </div>
                <div className="flex flex-col gap-1.5 sm:max-w-56">
                    <Label htmlFor="due_on">
                        {t('mobility.field.due_on')} ({t('common.optional')})
                    </Label>
                    <Input
                        id="due_on"
                        type="date"
                        className="h-11"
                        value={form.data.due_on}
                        onChange={(event) => form.setData('due_on', event.target.value)}
                    />
                    <InputError message={form.errors.due_on} />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('mobility.save_next_action')}
                </Button>
            </form>
        </section>
    );
}

function NoteDialog({
    mobilityCase,
    stage,
    action,
}: {
    mobilityCase: MobilityCase;
    stage: CaseStage;
    action: 'block' | 'skip';
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ note: '' });
    const Icon = action === 'block' ? Ban : SkipForward;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cases.stages.change.url([mobilityCase.id, stage.id, action]), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="min-h-11">
                    <Icon aria-hidden className="size-4" />
                    {t(`mobility.action.${action}`)}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t(`mobility.action.${action}_title`, { stage: stage.label })}
                    </DialogTitle>
                    <DialogDescription>{t('mobility.action.note_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <Label htmlFor={`${action}-note`}>{t('mobility.field.note')}</Label>
                    <textarea
                        id={`${action}-note`}
                        rows={3}
                        maxLength={500}
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        className={TEXTAREA}
                    />
                    <InputError
                        message={form.errors.note ?? (form.errors as Record<string, string>).stage}
                    />
                    <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {t(`mobility.action.${action}`)}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CloseDialog({
    mobilityCase,
    reasons,
}: {
    mobilityCase: MobilityCase;
    reasons: App.Domain.Mobility.Enums.CaseClosedReason[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ reason: '', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cases.close.url(mobilityCase.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="min-h-11 self-start text-bad">
                    {t('mobility.close.title')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('mobility.close.title')}</DialogTitle>
                    <DialogDescription>{t('mobility.close.body')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="close-reason">{t('mobility.close.reason')}</Label>
                        <NativeSelect
                            id="close-reason"
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                        >
                            <option value="" disabled>
                                {t('common.choose')}
                            </option>
                            {reasons.map((reason) => (
                                <option key={reason} value={reason}>
                                    {t(`mobility.closed_reason.${reason}`)}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.reason} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="close-note">
                            {t('mobility.close.note')}
                            {form.data.reason !== 'other' && ` (${t('common.optional')})`}
                        </Label>
                        <textarea
                            id="close-note"
                            rows={2}
                            maxLength={500}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            className={TEXTAREA}
                        />
                        <InputError message={form.errors.note} />
                    </div>
                    <Button
                        type="submit"
                        variant="destructive"
                        className="min-h-11 self-end"
                        disabled={form.processing || form.data.reason === ''}
                    >
                        {form.processing && <Spinner />}
                        {t('mobility.close.confirm')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
