import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Ban,
    Check,
    CircleAlert,
    FilePlus,
    MessageSquare,
    Play,
    SkipForward,
    Undo2,
    Upload,
} from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { FileUpload } from '@/components/bs/file-upload';
import type { CaseDocument } from '@/components/bs/mobility/case-documents';
import { CaseDocumentRow, DOCUMENT_ACCEPT } from '@/components/bs/mobility/case-documents';
import { CaseHistory, trackerStages } from '@/components/bs/mobility/case-history';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StageTracker } from '@/components/bs/stage-tracker';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    documents: CaseDocument[];
    documentTypes: App.Domain.Verification.Enums.DocumentType[];
    returnReasons: App.Domain.Mobility.Enums.CaseDocumentReturnReason[];
    maxSizeMb: number;
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** One mobility case: the managing organization runs its stages (spec §6, product rule 8). */
export default function OrgCaseShow({
    mobilityCase,
    closeReasons,
    canManage,
    documents,
    documentTypes,
    returnReasons,
    maxSizeMb,
}: Props) {
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
                        <Panel title={t('mobility.document.title')}>
                            {documents.length === 0 ? (
                                <p className="text-sm text-muted">{t('mobility.document.empty')}</p>
                            ) : (
                                <ul className="flex flex-col divide-y divide-line">
                                    {documents.map((document) => (
                                        <CaseDocumentRow
                                            key={document.id}
                                            document={document}
                                            viewer="organization"
                                        >
                                            {canManage && (
                                                <OrgDocumentActions
                                                    mobilityCase={mobilityCase}
                                                    document={document}
                                                    returnReasons={returnReasons}
                                                    maxSizeMb={maxSizeMb}
                                                />
                                            )}
                                        </CaseDocumentRow>
                                    ))}
                                </ul>
                            )}
                            {canManage && open && (
                                <RequestDocumentDialog
                                    mobilityCase={mobilityCase}
                                    types={documentTypes}
                                />
                            )}
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

function OrgDocumentActions({
    mobilityCase,
    document,
    returnReasons,
    maxSizeMb,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
    returnReasons: App.Domain.Mobility.Enums.CaseDocumentReturnReason[];
    maxSizeMb: number;
}) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | undefined>();

    if (document.providedBy === 'organization') {
        return document.status.value === 'doc_accepted' ? null : (
            <OrgUpload mobilityCase={mobilityCase} document={document} maxSizeMb={maxSizeMb} />
        );
    }

    if (document.status.value !== 'doc_uploaded') {
        return null;
    }

    return (
        <div className="flex flex-col gap-2">
            <div className="flex flex-wrap gap-2">
                <Button
                    className="min-h-11"
                    disabled={processing}
                    onClick={() =>
                        router.post(
                            org.cases.documents.decide.url([
                                mobilityCase.id,
                                document.id,
                                'accept',
                            ]),
                            {},
                            {
                                preserveScroll: true,
                                onStart: () => setProcessing(true),
                                onFinish: () => setProcessing(false),
                                onError: (errors) => setError(errors.document),
                            },
                        )
                    }
                >
                    {processing ? <Spinner /> : <Check aria-hidden className="size-4" />}
                    {t('mobility.document.accept')}
                </Button>
                <ReturnDialog
                    mobilityCase={mobilityCase}
                    document={document}
                    reasons={returnReasons}
                />
            </div>
            <InputError message={error} />
        </div>
    );
}

/** Files the organization provides: contract, work permit, visa. */
function OrgUpload({
    mobilityCase,
    document,
    maxSizeMb,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
    maxSizeMb: number;
}) {
    const { t } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [employerCanView, setEmployerCanView] = useState(document.employerCanView);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const id = `upload-${document.id}`;

    return (
        <div className="flex flex-col gap-3 rounded-xl bg-surface-2 p-3">
            <FileUpload
                label={
                    document.fileUrl
                        ? t('mobility.document.replace_file')
                        : t('mobility.document.upload_label')
                }
                value={file}
                onChange={setFile}
                accept={DOCUMENT_ACCEPT}
                acceptLabel="PDF, JPG, PNG"
                maxSizeMb={maxSizeMb}
                error={error}
                sensitive
            />
            {mobilityCase.agency !== null && (
                <div className="flex items-start gap-2">
                    <Checkbox
                        id={`${id}-employer`}
                        checked={employerCanView}
                        onCheckedChange={(checked) => setEmployerCanView(checked === true)}
                        className="mt-0.5"
                    />
                    <Label htmlFor={`${id}-employer`} className="text-sm font-normal">
                        {t('mobility.document.employer_sees', { employer: mobilityCase.employer })}
                    </Label>
                </div>
            )}
            <p className="text-xs text-muted">
                {document.type === 'contract'
                    ? t('mobility.document.contract_upload_hint')
                    : t('mobility.document.org_upload_hint')}
            </p>
            <Button
                className="min-h-11 self-start"
                disabled={!file || processing}
                onClick={() =>
                    router.post(
                        org.cases.documents.upload.url([mobilityCase.id, document.id]),
                        { document: file, employer_can_view: employerCanView },
                        {
                            forceFormData: true,
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                            onError: (errors) => setError(errors.document),
                            onSuccess: () => setFile(null),
                        },
                    )
                }
            >
                {processing ? <Spinner /> : <Upload aria-hidden className="size-4" />}
                {t('mobility.document.upload')}
            </Button>
        </div>
    );
}

function ReturnDialog({
    mobilityCase,
    document,
    reasons,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
    reasons: App.Domain.Mobility.Enums.CaseDocumentReturnReason[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ reason: '', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cases.documents.decide.url([mobilityCase.id, document.id, 'return']), {
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
                    <Undo2 aria-hidden className="size-4" />
                    {t('mobility.document.return')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('mobility.document.return_title', { document: document.title })}
                    </DialogTitle>
                    <DialogDescription>{t('mobility.document.return_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`return-reason-${document.id}`}>
                            {t('mobility.document.return_reason_label')}
                        </Label>
                        <NativeSelect
                            id={`return-reason-${document.id}`}
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                        >
                            <option value="" disabled>
                                {t('common.choose')}
                            </option>
                            {reasons.map((reason) => (
                                <option key={reason} value={reason}>
                                    {t(`mobility.document.return_reason.${reason}`)}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.reason} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`return-note-${document.id}`}>
                            {t('mobility.field.note')}
                            {form.data.reason !== 'other' && ` (${t('common.optional')})`}
                        </Label>
                        <textarea
                            id={`return-note-${document.id}`}
                            rows={3}
                            maxLength={500}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            className={TEXTAREA}
                        />
                        <InputError
                            message={
                                form.errors.note ?? (form.errors as Record<string, string>).document
                            }
                        />
                    </div>
                    <Button
                        type="submit"
                        className="min-h-11 self-end"
                        disabled={form.processing || form.data.reason === ''}
                    >
                        {form.processing && <Spinner />}
                        {t('mobility.document.return')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Ask for a document beyond the destination's checklist. */
function RequestDocumentDialog({
    mobilityCase,
    types,
}: {
    mobilityCase: MobilityCase;
    types: App.Domain.Verification.Enums.DocumentType[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        type: '',
        title: '',
        provided_by: 'worker',
        due_on: '',
        note: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cases.documents.store.url(mobilityCase.id), {
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
                <Button variant="outline" className="min-h-11 self-start">
                    <FilePlus aria-hidden className="size-4" />
                    {t('mobility.document.request')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('mobility.document.request')}</DialogTitle>
                    <DialogDescription>{t('mobility.document.request_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="request-type">{t('documents.type')}</Label>
                        <NativeSelect
                            id="request-type"
                            value={form.data.type}
                            onChange={(event) => form.setData('type', event.target.value)}
                        >
                            <option value="" disabled>
                                {t('common.choose')}
                            </option>
                            {types.map((type) => (
                                <option key={type} value={type}>
                                    {t(`mobility.document.type.${type}`)}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.type} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="request-title">
                            {t('mobility.document.request_title')} ({t('common.optional')})
                        </Label>
                        <Input
                            id="request-title"
                            className="h-11"
                            maxLength={120}
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="request-provider">
                            {t('mobility.document.request_provider')}
                        </Label>
                        <NativeSelect
                            id="request-provider"
                            value={form.data.provided_by}
                            onChange={(event) => form.setData('provided_by', event.target.value)}
                        >
                            <option value="worker">{t('mobility.document.from_worker')}</option>
                            <option value="organization">
                                {t('mobility.document.from_organization')}
                            </option>
                        </NativeSelect>
                        <InputError message={form.errors.provided_by} />
                    </div>
                    <div className="flex flex-col gap-1.5 sm:max-w-56">
                        <Label htmlFor="request-due">
                            {t('mobility.field.due_on')} ({t('common.optional')})
                        </Label>
                        <Input
                            id="request-due"
                            type="date"
                            className="h-11"
                            value={form.data.due_on}
                            onChange={(event) => form.setData('due_on', event.target.value)}
                        />
                        <InputError message={form.errors.due_on} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="request-note">
                            {t('mobility.document.request_note')} ({t('common.optional')})
                        </Label>
                        <textarea
                            id="request-note"
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
                        className="min-h-11 self-end"
                        disabled={form.processing || form.data.type === ''}
                    >
                        {form.processing && <Spinner />}
                        {t('mobility.document.request_send')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
