import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Ban, FileText, RotateCcw } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { ReasonDialog } from '@/components/bs/trust/reason-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type TrustCase = App.Domain.Trust.Data.TrustCaseData;
type Reason = App.Domain.Trust.Enums.TrustReason;

type Props = {
    trustCase: TrustCase;
    officers: { id: string; name: string }[];
    actionReasons: Reason[];
    restoreReasons: Reason[];
    closeReasons: Reason[];
    priorities: App.Domain.Trust.Enums.CasePriority[];
    subjectSuspended: boolean;
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** One trust case: reports, evidence, suspensions and appeals, outcome (spec §10). */
export default function TrustCaseShow({
    trustCase,
    officers,
    actionReasons,
    restoreReasons,
    closeReasons,
    priorities,
    subjectSuspended,
}: Props) {
    const { t, formatDate } = useTranslation();
    const summary = trustCase.summary;
    const closed = summary.status.value === 'trust_closed';
    const { auth } = usePage().props;

    return (
        <>
            <Head title={summary.reference} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={`${summary.reference} · ${t(`report.category.${summary.category}`)}`}
                    title={summary.subject}
                    description={t(`trust.about.${summary.subjectType}`)}
                    back={{ href: admin.trust.cases.index(), label: t('trust.cases.title') }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={summary.status} />
                            <StatusChip status={summary.priority} icon={false} />
                            {subjectSuspended && (
                                <StatusChip tone="bad" label={t('trust.suspended')} />
                            )}
                            {!closed &&
                                (subjectSuspended ? (
                                    <ReasonDialog
                                        id="restore"
                                        trigger={
                                            <Button variant="outline" className="min-h-11">
                                                <RotateCcw aria-hidden className="size-4" />
                                                {t('trust.restore')}
                                            </Button>
                                        }
                                        title={t('trust.restore_title', {
                                            subject: summary.subject,
                                        })}
                                        description={t('trust.restore_hint')}
                                        action={admin.trust.cases.actions.store.url([
                                            summary.id,
                                            'restore',
                                        ])}
                                        reasons={restoreReasons}
                                        noteRequired
                                        submitLabel={t('trust.restore')}
                                    />
                                ) : (
                                    <ReasonDialog
                                        id="suspend"
                                        trigger={
                                            <Button variant="destructive" className="min-h-11">
                                                <Ban aria-hidden className="size-4" />
                                                {t('trust.suspend')}
                                            </Button>
                                        }
                                        title={t('trust.suspend_title', {
                                            subject: summary.subject,
                                        })}
                                        description={t(`trust.suspend_hint.${summary.subjectType}`)}
                                        action={admin.trust.cases.actions.store.url([
                                            summary.id,
                                            'suspend',
                                        ])}
                                        reasons={actionReasons}
                                        noteRequired
                                        submitLabel={t('trust.suspend')}
                                        destructive
                                    />
                                ))}
                        </div>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
                    <div className="flex min-w-0 flex-col gap-5">
                        <Panel title={t('trust.summary')}>
                            <p className="text-sm whitespace-pre-line">{trustCase.summaryText}</p>
                        </Panel>

                        <Panel title={t('trust.case.reports', { count: trustCase.reports.length })}>
                            {trustCase.reports.length === 0 ? (
                                <p className="text-sm text-muted">{t('trust.case.no_reports')}</p>
                            ) : (
                                <ul className="flex flex-col divide-y divide-line">
                                    {trustCase.reports.map((report) => (
                                        <li
                                            key={report.id}
                                            className="flex flex-col gap-1 py-2.5 text-sm"
                                        >
                                            <span className="flex flex-wrap items-center justify-between gap-2">
                                                <span className="font-semibold">
                                                    {t(`report.category.${report.category}`)}
                                                </span>
                                                <StatusChip status={report.status} />
                                            </span>
                                            {report.excerpt && (
                                                <span className="text-xs italic">
                                                    “{report.excerpt}”
                                                </span>
                                            )}
                                            {report.description && (
                                                <span className="whitespace-pre-line">
                                                    {report.description}
                                                </span>
                                            )}
                                            <span className="text-xs text-muted">
                                                {report.reporter ?? '—'} ·{' '}
                                                {formatDate(report.createdAt)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>

                        <Panel title={t('trust.case.evidence')}>
                            {trustCase.notes.length > 0 && (
                                <ol className="flex flex-col gap-3">
                                    {trustCase.notes.map((note) => (
                                        <li
                                            key={note.id}
                                            className="flex flex-col gap-1 border-s-2 border-line ps-3 text-sm"
                                        >
                                            <span className="text-xs text-muted">
                                                {note.author ?? '—'} ·{' '}
                                                {formatDate(note.at, {
                                                    dateStyle: 'medium',
                                                    timeStyle: 'short',
                                                })}
                                            </span>
                                            <span className="whitespace-pre-line">{note.body}</span>
                                            {note.files.map((file, index) => (
                                                <a
                                                    key={index}
                                                    href={file.url ?? '#'}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex min-h-11 items-center gap-1.5 self-start font-semibold text-brand hover:underline"
                                                >
                                                    <FileText aria-hidden className="size-4" />
                                                    {file.name}
                                                </a>
                                            ))}
                                        </li>
                                    ))}
                                </ol>
                            )}
                            {!closed && <NoteForm caseId={summary.id} />}
                        </Panel>

                        <Panel title={t('trust.case.actions')}>
                            {trustCase.actions.length === 0 ? (
                                <p className="text-sm text-muted">{t('trust.case.no_actions')}</p>
                            ) : (
                                <ol className="flex flex-col gap-3">
                                    {trustCase.actions.map((action) => (
                                        <li
                                            key={action.id}
                                            className="flex flex-col gap-2 border-s-2 border-line ps-3 text-sm"
                                        >
                                            <span className="flex flex-wrap items-center gap-2">
                                                <StatusChip
                                                    tone={
                                                        action.action === 'suspend' ? 'bad' : 'ok'
                                                    }
                                                    label={t(`trust.action.${action.action}`)}
                                                />
                                                <span className="font-semibold">
                                                    {t(`trust_reason.${action.reason}`)}
                                                </span>
                                            </span>
                                            {action.note && (
                                                <span className="whitespace-pre-line">
                                                    {action.note}
                                                </span>
                                            )}
                                            <span className="text-xs text-muted">
                                                {action.actor ?? '—'} ·{' '}
                                                {formatDate(action.at, {
                                                    dateStyle: 'medium',
                                                    timeStyle: 'short',
                                                })}
                                            </span>
                                            {action.appeals.map((appeal) => (
                                                <div
                                                    key={appeal.id}
                                                    className="flex flex-col gap-1.5 rounded-lg bg-surface-2 p-3"
                                                >
                                                    <span className="flex flex-wrap items-center justify-between gap-2">
                                                        <span className="font-semibold">
                                                            {t('trust.case.appeal_from', {
                                                                name: appeal.appellant ?? '—',
                                                            })}
                                                        </span>
                                                        <StatusChip status={appeal.status} />
                                                    </span>
                                                    <span className="whitespace-pre-line">
                                                        {appeal.statement}
                                                    </span>
                                                    {appeal.decisionNote && (
                                                        <span className="text-xs">
                                                            {appeal.decider}: {appeal.decisionNote}
                                                        </span>
                                                    )}
                                                    {appeal.status.value === 'appeal_submitted' &&
                                                        (action.actorId === auth.user?.id ? (
                                                            <span className="text-xs text-muted">
                                                                {t(
                                                                    'trust.case.appeal_other_officer',
                                                                )}
                                                            </span>
                                                        ) : (
                                                            <span className="flex flex-wrap gap-2">
                                                                {(
                                                                    ['overturn', 'uphold'] as const
                                                                ).map((decision) => (
                                                                    <ReasonDialog
                                                                        key={decision}
                                                                        id={`${decision}-${appeal.id}`}
                                                                        trigger={
                                                                            <Button
                                                                                size="sm"
                                                                                variant={
                                                                                    decision ===
                                                                                    'overturn'
                                                                                        ? 'default'
                                                                                        : 'outline'
                                                                                }
                                                                                className="min-h-11"
                                                                            >
                                                                                {t(
                                                                                    `trust.appeal.${decision}`,
                                                                                )}
                                                                            </Button>
                                                                        }
                                                                        title={t(
                                                                            `trust.appeal.${decision}_title`,
                                                                        )}
                                                                        description={t(
                                                                            'trust.appeal.hint',
                                                                        )}
                                                                        action={admin.trust.appeals.decide.url(
                                                                            [appeal.id, decision],
                                                                        )}
                                                                        noteRequired
                                                                        submitLabel={t(
                                                                            `trust.appeal.${decision}`,
                                                                        )}
                                                                    />
                                                                ))}
                                                            </span>
                                                        ))}
                                                </div>
                                            ))}
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </Panel>
                    </div>

                    <div className="flex min-w-0 flex-col gap-5">
                        {!closed && (
                            <AssignForm
                                trustCase={trustCase}
                                officers={officers}
                                priorities={priorities}
                            />
                        )}
                        {closed ? (
                            <Panel title={t('trust.case.closed')}>
                                <p className="text-sm">
                                    {trustCase.outcomeCode &&
                                        t(`trust_reason.${trustCase.outcomeCode}`)}
                                    {trustCase.closedAt && ` · ${formatDate(trustCase.closedAt)}`}
                                </p>
                            </Panel>
                        ) : (
                            <ReasonDialog
                                id="close"
                                trigger={
                                    <Button variant="outline" className="min-h-11 self-start">
                                        {t('trust.close')}
                                    </Button>
                                }
                                title={t('trust.close_title')}
                                description={t('trust.close_hint')}
                                action={admin.trust.cases.close.url(summary.id)}
                                reasons={closeReasons}
                                submitLabel={t('trust.close')}
                            />
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

function NoteForm({ caseId }: { caseId: string }) {
    const { t } = useTranslation();
    const [body, setBody] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            admin.trust.cases.notes.store.url(caseId),
            { body, files },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setErrors,
                onSuccess: () => {
                    setBody('');
                    setFiles([]);
                    setErrors({});
                },
            },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-2 border-t border-line pt-3"
            noValidate
        >
            <Label htmlFor="case-note">{t('trust.case.add_note')}</Label>
            <textarea
                id="case-note"
                rows={3}
                maxLength={5000}
                value={body}
                onChange={(event) => setBody(event.target.value)}
                className={TEXTAREA}
            />
            <InputError message={errors.body} />
            <Label htmlFor="case-files" className="text-xs text-muted">
                {t('trust.case.files')}
            </Label>
            <input
                id="case-files"
                type="file"
                multiple
                accept="application/pdf,image/jpeg,image/png"
                onChange={(event) => setFiles(Array.from(event.target.files ?? []).slice(0, 5))}
                className="text-sm file:me-3 file:min-h-11 file:rounded-md file:border file:border-line file:bg-surface file:px-3"
            />
            {Object.entries(errors)
                .filter(([key]) => key.startsWith('files'))
                .map(([key, message]) => (
                    <InputError key={key} message={message} />
                ))}
            <Button
                type="submit"
                variant="outline"
                className="min-h-11 self-start"
                disabled={processing || body.trim() === ''}
            >
                {processing && <Spinner />}
                {t('trust.case.save_note')}
            </Button>
        </form>
    );
}

function AssignForm({
    trustCase,
    officers,
    priorities,
}: {
    trustCase: TrustCase;
    officers: { id: string; name: string }[];
    priorities: App.Domain.Trust.Enums.CasePriority[];
}) {
    const { t } = useTranslation();
    const form = useForm({
        assigned_to: trustCase.assigneeId ?? '',
        priority: trustCase.summary.priority.value.replace('priority_', ''),
        investigate: false,
    });
    const isOpen = trustCase.summary.status.value === 'trust_open';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(admin.trust.cases.update.url(trustCase.summary.id), { preserveScroll: true });
    };

    return (
        <Panel title={t('trust.case.handling')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="assignee">{t('trust.assignee')}</Label>
                    <NativeSelect
                        id="assignee"
                        value={form.data.assigned_to}
                        onChange={(event) => form.setData('assigned_to', event.target.value)}
                    >
                        <option value="">{t('trust.unassigned')}</option>
                        {officers.map((officer) => (
                            <option key={officer.id} value={officer.id}>
                                {officer.name}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="priority">{t('trust.priority')}</Label>
                    <NativeSelect
                        id="priority"
                        value={form.data.priority}
                        onChange={(event) => form.setData('priority', event.target.value)}
                    >
                        {priorities.map((priority) => (
                            <option key={priority} value={priority}>
                                {t(`status.priority_${priority}`)}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                {isOpen && (
                    <div className="flex min-h-11 items-center gap-2">
                        <Checkbox
                            id="investigate"
                            checked={form.data.investigate}
                            onCheckedChange={(checked) =>
                                form.setData('investigate', checked === true)
                            }
                        />
                        <Label htmlFor="investigate" className="font-normal">
                            {t('trust.case.start_investigating')}
                        </Label>
                    </div>
                )}
                <Button
                    type="submit"
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('common.save')}
                </Button>
            </form>
        </Panel>
    );
}
