import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    CalendarClock,
    Check,
    CircleAlert,
    EyeOff,
    MessageSquare,
    ShieldAlert,
    Upload,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { FileUpload } from '@/components/bs/file-upload';
import type { CaseDocument } from '@/components/bs/mobility/case-documents';
import { CaseDocumentRow, DOCUMENT_ACCEPT } from '@/components/bs/mobility/case-documents';
import { CaseHistory, trackerStages } from '@/components/bs/mobility/case-history';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import worker from '@/routes/worker';

type MobilityCase = App.Domain.Mobility.Data.MobilityCaseData;
type Scan = App.Domain.Worker.Data.IdentityDocumentData;

type Props = {
    mobilityCase: MobilityCase;
    documents: CaseDocument[];
    scansOnFile: Scan[];
    canRespond: boolean;
    maxSizeMb: number;
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** The worker's journey after accepting an offer (prototype w-case, spec §6). */
export default function WorkerCaseShow({
    mobilityCase,
    documents,
    scansOnFile,
    canRespond,
    maxSizeMb,
}: Props) {
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

                <section
                    aria-labelledby="case-documents-heading"
                    className="flex flex-col gap-1 rounded-2xl border border-line bg-surface p-5"
                >
                    <h2 id="case-documents-heading" className="font-semibold">
                        {t('mobility.document.title')}
                    </h2>
                    <p className="text-sm text-muted">{t('mobility.document.worker_intro')}</p>
                    {documents.length === 0 ? (
                        <p className="py-3 text-sm text-muted">{t('mobility.document.empty')}</p>
                    ) : (
                        <ul className="flex flex-col divide-y divide-line">
                            {documents.map((document) => (
                                <CaseDocumentRow
                                    key={document.id}
                                    document={document}
                                    viewer="worker"
                                >
                                    {canRespond && (
                                        <WorkerDocumentActions
                                            mobilityCase={mobilityCase}
                                            document={document}
                                            scan={scansOnFile.find(
                                                (scan) => scan.type === document.type,
                                            )}
                                            maxSizeMb={maxSizeMb}
                                        />
                                    )}
                                </CaseDocumentRow>
                            ))}
                        </ul>
                    )}
                </section>

                <CaseHistory events={mobilityCase.events} />
            </div>
        </>
    );
}

function WorkerDocumentActions({
    mobilityCase,
    document,
    scan,
    maxSizeMb,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
    scan: Scan | undefined;
    maxSizeMb: number;
}) {
    if (document.providedBy === 'organization') {
        return document.status.value === 'doc_contract_sent' ? (
            <ContractAnswer mobilityCase={mobilityCase} document={document} />
        ) : null;
    }

    if (document.status.value === 'doc_accepted') {
        return null;
    }

    const shared = document.visibleTo.length > 0;

    return (
        <div className="flex flex-col gap-3">
            {shared && <SharedControls mobilityCase={mobilityCase} document={document} />}
            {document.status.value !== 'doc_uploaded' && (
                <ShareForm
                    mobilityCase={mobilityCase}
                    document={document}
                    scan={scan}
                    maxSizeMb={maxSizeMb}
                />
            )}
        </div>
    );
}

/** Upload a file, or share the passport/ID scan already on the Documents page (product rule 6). */
function ShareForm({
    mobilityCase,
    document,
    scan,
    maxSizeMb,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
    scan: Scan | undefined;
    maxSizeMb: number;
}) {
    const { t, formatDate } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [employerCanView, setEmployerCanView] = useState(document.employerCanView);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const separateEmployer = mobilityCase.agency !== null;
    const id = `share-${document.id}`;

    const share = (payload: { document?: File; document_id?: string }) =>
        router.post(
            worker.cases.documents.share.url([mobilityCase.id, document.id]),
            { ...payload, employer_can_view: separateEmployer ? employerCanView : true },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setErrors,
                onSuccess: () => {
                    setFile(null);
                    setErrors({});
                },
            },
        );

    return (
        <div className="flex flex-col gap-3 rounded-xl bg-surface-2 p-3">
            {separateEmployer && (
                <div className="flex items-start gap-2">
                    <Checkbox
                        id={`${id}-employer`}
                        checked={employerCanView}
                        onCheckedChange={(checked) => setEmployerCanView(checked === true)}
                        className="mt-0.5"
                    />
                    <Label htmlFor={`${id}-employer`} className="text-sm font-normal">
                        {t('mobility.document.also_employer', {
                            agency: mobilityCase.managedBy,
                            employer: mobilityCase.employer,
                        })}
                    </Label>
                </div>
            )}
            {scan && (
                <Button
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={processing}
                    onClick={() => share({ document_id: scan.id })}
                >
                    {processing ? <Spinner /> : <Check aria-hidden className="size-4" />}
                    {t('mobility.document.use_scan', { date: formatDate(scan.uploadedAt) })}
                </Button>
            )}
            <FileUpload
                label={
                    scan ? t('mobility.document.or_upload') : t('mobility.document.upload_label')
                }
                value={file}
                onChange={setFile}
                accept={DOCUMENT_ACCEPT}
                acceptLabel="PDF, JPG, PNG"
                maxSizeMb={maxSizeMb}
                error={errors.document}
                sensitive
            />
            <InputError message={errors.document_id} />
            <Button
                className="min-h-11 self-start"
                disabled={!file || processing}
                onClick={() => file && share({ document: file })}
            >
                {processing ? <Spinner /> : <Upload aria-hidden className="size-4" />}
                {t('mobility.document.share')}
            </Button>
        </div>
    );
}

/** A shared item: who else sees it, and stop sharing. */
function SharedControls({
    mobilityCase,
    document,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
}) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);
    const id = `visible-${document.id}`;

    return (
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
            {mobilityCase.agency !== null && (
                <div className="flex min-h-11 items-center gap-2">
                    <Checkbox
                        id={id}
                        checked={document.employerCanView}
                        disabled={processing}
                        onCheckedChange={(checked) =>
                            router.put(
                                worker.cases.documents.visibility.url([
                                    mobilityCase.id,
                                    document.id,
                                ]),
                                { employer_can_view: checked === true },
                                {
                                    preserveScroll: true,
                                    onStart: () => setProcessing(true),
                                    onFinish: () => setProcessing(false),
                                },
                            )
                        }
                    />
                    <Label htmlFor={id} className="text-sm font-normal">
                        {t('mobility.document.employer_sees', { employer: mobilityCase.employer })}
                    </Label>
                </div>
            )}
            <Button
                variant="ghost"
                className="min-h-11 text-bad"
                disabled={processing}
                onClick={() =>
                    router.delete(
                        worker.cases.documents.revoke.url([mobilityCase.id, document.id]),
                        {
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                        },
                    )
                }
            >
                <EyeOff aria-hidden className="size-4" />
                {t('mobility.document.stop_sharing')}
            </Button>
        </div>
    );
}

/** Confirm the contract the organization sent, or say what needs changing (D-079). */
function ContractAnswer({
    mobilityCase,
    document,
}: {
    mobilityCase: MobilityCase;
    document: CaseDocument;
}) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | undefined>();
    const [open, setOpen] = useState(false);
    const form = useForm({ note: '' });

    const query = (event: FormEvent) => {
        event.preventDefault();
        form.post(worker.cases.documents.contract.url([mobilityCase.id, document.id, 'query']), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <div className="flex flex-col gap-2 rounded-xl border-2 border-stamp/40 bg-stamp-soft p-3">
            <p className="text-sm">{t('mobility.document.contract_hint')}</p>
            <div className="flex flex-wrap gap-2">
                <Button
                    className="min-h-11"
                    disabled={processing}
                    onClick={() =>
                        router.post(
                            worker.cases.documents.contract.url([
                                mobilityCase.id,
                                document.id,
                                'confirm',
                            ]),
                            {},
                            {
                                preserveScroll: true,
                                onStart: () => setProcessing(true),
                                onFinish: () => setProcessing(false),
                                onError: (errors) => setError(errors.contract),
                            },
                        )
                    }
                >
                    {processing ? <Spinner /> : <Check aria-hidden className="size-4" />}
                    {t('mobility.document.contract_confirm')}
                </Button>
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button variant="outline" className="min-h-11">
                            {t('mobility.document.contract_query')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{t('mobility.document.contract_query')}</DialogTitle>
                            <DialogDescription>
                                {t('mobility.document.contract_query_hint', {
                                    org: mobilityCase.managedBy,
                                })}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={query} className="flex flex-col gap-3" noValidate>
                            <Label htmlFor={`query-${document.id}`}>
                                {t('mobility.document.contract_query_label')}
                            </Label>
                            <textarea
                                id={`query-${document.id}`}
                                rows={3}
                                maxLength={500}
                                value={form.data.note}
                                onChange={(event) => form.setData('note', event.target.value)}
                                className={TEXTAREA}
                            />
                            <InputError
                                message={
                                    form.errors.note ??
                                    (form.errors as Record<string, string>).contract
                                }
                            />
                            <Button
                                type="submit"
                                className="min-h-11 self-end"
                                disabled={form.processing}
                            >
                                {form.processing && <Spinner />}
                                {t('mobility.document.contract_query_send')}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
            <InputError message={error} />
        </div>
    );
}
