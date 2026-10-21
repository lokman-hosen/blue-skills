import { router } from '@inertiajs/react';
import { Eye, EyeOff, FileText, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import PassportController from '@/actions/App/Http/Controllers/Worker/PassportController';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { EmptyState } from '@/components/bs/empty-state';
import { EvidenceBadge } from '@/components/bs/evidence-badge';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import { FileUpload } from '@/components/bs/file-upload';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

type Props = {
    evidence: App.Domain.Worker.Data.EvidenceSummaryData[];
    types: App.Domain.Verification.Enums.EvidenceType[];
    maxSizeMb: number;
};

/**
 * Certificates, training records and work references (D-047). Each one is verified on its
 * own (product rule 9) and is never shared without per-application consent (product rule 6).
 */
export function EvidenceUploadSection({ evidence, types, maxSizeMb }: Props) {
    const { t, formatDate } = useTranslation();
    const [type, setType] = useState<string>(types[0] ?? 'certificate');
    const [title, setTitle] = useState('');
    const [expiresOn, setExpiresOn] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            PassportController.storeCredential.url(),
            { type, title, expires_on: expiresOn || null, document: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setErrors,
                onSuccess: () => {
                    setTitle('');
                    setExpiresOn('');
                    setFile(null);
                    setErrors({});
                },
            },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            {evidence.length > 0 ? (
                <ul className="flex flex-col gap-2">
                    {evidence.map((item) => (
                        <li
                            key={item.id}
                            className="flex items-center gap-3 rounded-xl border border-line bg-surface p-3"
                        >
                            <FileText aria-hidden className="size-5 shrink-0 text-brand" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-semibold">
                                    {item.title ?? t(`evidence.type.${item.type}`)}
                                </p>
                                <p className="text-xs text-muted">
                                    {t(`evidence.type.${item.type}`)}
                                    {item.issuer &&
                                        ` · ${t('evidence.issued_by', { issuer: item.issuer })}`}
                                    {item.expiresOn &&
                                        ` · ${t('worker.credential.expires', { date: formatDate(item.expiresOn) })}`}
                                </p>
                                {item.reasonCode === 'withdrawn_by_issuer' && (
                                    <p className="text-xs text-bad">
                                        {t('evidence.withdrawn_by_issuer')}
                                    </p>
                                )}
                                {item.hidden && (
                                    <p className="text-xs text-muted">
                                        {t('evidence.hidden_note')}
                                    </p>
                                )}
                            </div>
                            <EvidenceBadge state={item.state as EvidenceState} />
                            {item.hideable && (
                                <Button
                                    variant="ghost"
                                    className="size-11 shrink-0"
                                    aria-label={`${item.hidden ? t('evidence.show') : t('evidence.hide')}: ${item.title ?? ''}`}
                                    aria-pressed={item.hidden}
                                    onClick={() =>
                                        router.put(
                                            PassportController.updateEvidenceVisibility.url(
                                                item.id,
                                            ),
                                            { hidden: !item.hidden },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {item.hidden ? (
                                        <Eye aria-hidden className="size-4" />
                                    ) : (
                                        <EyeOff aria-hidden className="size-4" />
                                    )}
                                </Button>
                            )}
                            {item.removable && (
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            className="size-11 shrink-0 text-bad"
                                            aria-label={`${t('common.delete')}: ${item.title ?? ''}`}
                                        >
                                            <Trash2 aria-hidden className="size-4" />
                                        </Button>
                                    }
                                    title={t('worker.credential.remove_title')}
                                    description={t('worker.credential.remove_body')}
                                    confirmLabel={t('common.delete')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            PassportController.destroyCredential.url(item.id),
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            )}
                        </li>
                    ))}
                </ul>
            ) : (
                <EmptyState
                    icon={FileText}
                    title={t('worker.credential.empty_title')}
                    description={t('worker.credential.empty_body')}
                />
            )}

            <form
                onSubmit={submit}
                className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5"
                noValidate
            >
                <h2 className="font-semibold">{t('worker.credential.add')}</h2>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="credential-type">{t('worker.credential.kind')}</Label>
                    <NativeSelect
                        id="credential-type"
                        value={type}
                        onChange={(event) => setType(event.target.value)}
                    >
                        {types.map((value) => (
                            <option key={value} value={value}>
                                {t(`evidence.type.${value}`)}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={errors.type} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="credential-title">{t('worker.credential.title')}</Label>
                    <Input
                        id="credential-title"
                        value={title}
                        maxLength={120}
                        onChange={(event) => setTitle(event.target.value)}
                        placeholder={t('worker.credential.title_example')}
                        className="h-11 text-base"
                        aria-invalid={!!errors.title}
                    />
                    <InputError message={errors.title} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="credential-expires">
                        {t('worker.credential.expires_on')} ({t('common.optional')})
                    </Label>
                    <Input
                        id="credential-expires"
                        type="date"
                        value={expiresOn}
                        onChange={(event) => setExpiresOn(event.target.value)}
                        className="h-11 max-w-56 text-base"
                    />
                    <InputError message={errors.expires_on} />
                </div>
                <FileUpload
                    label={t('worker.credential.file')}
                    value={file}
                    onChange={(selected) => setFile(selected)}
                    accept={['application/pdf', 'image/jpeg', 'image/png']}
                    acceptLabel="PDF, JPG, PNG"
                    maxSizeMb={maxSizeMb}
                    error={errors.document}
                    sensitive
                />
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('worker.credential.review_note')}
                </p>
                <Button
                    type="submit"
                    className="min-h-12 self-start"
                    disabled={processing || !file || title.trim() === ''}
                >
                    {processing && <Spinner />}
                    {t('worker.credential.upload')}
                </Button>
            </form>
        </div>
    );
}
