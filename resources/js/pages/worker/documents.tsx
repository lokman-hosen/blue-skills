import { Head, router } from '@inertiajs/react';
import { FileText, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { EmptyState } from '@/components/bs/empty-state';
import { EvidenceBadge } from '@/components/bs/evidence-badge';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import { FileUpload } from '@/components/bs/file-upload';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import worker from '@/routes/worker';

type DocumentType = App.Domain.Verification.Enums.DocumentType;

type Props = {
    documents: App.Domain.Worker.Data.IdentityDocumentData[];
    types: DocumentType[];
    maxSizeMb: number;
};

/**
 * Passport and national ID scans (D-047). Never in search results; shared only with the
 * applications the worker chooses (product rule 6).
 */
export default function Documents({ documents, types, maxSizeMb }: Props) {
    const { t, formatDate } = useTranslation();
    const [type, setType] = useState<DocumentType>(types[0] ?? 'passport');
    const [expiresOn, setExpiresOn] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const replacing = documents.find((document) => document.type === type);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            worker.documents.store.url(),
            { type, expires_on: expiresOn || null, document: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setErrors,
                onSuccess: () => {
                    setExpiresOn('');
                    setFile(null);
                    setErrors({});
                },
            },
        );
    };

    return (
        <>
            <Head title={t('nav.documents')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader title={t('nav.documents')} description={t('documents.body')} />

                <section
                    aria-labelledby="documents-heading"
                    className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
                >
                    <h2 id="documents-heading" className="font-semibold">
                        {t('documents.list_title')}
                    </h2>
                    {documents.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title={t('documents.empty_title')}
                            description={t('documents.empty_body')}
                        />
                    ) : (
                        <ul className="flex flex-col divide-y divide-line">
                            {documents.map((document) => (
                                <li key={document.id} className="flex flex-col gap-2 py-3">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="font-semibold">
                                                {t(`document_type.${document.type}`)}
                                            </p>
                                            <p className="text-xs text-muted">
                                                {t('documents.uploaded_on', {
                                                    date: formatDate(document.uploadedAt),
                                                })}
                                                {document.expiresOn &&
                                                    ` · ${t('documents.expires_on', { date: formatDate(document.expiresOn) })}`}
                                            </p>
                                        </div>
                                        {document.state && (
                                            <EvidenceBadge
                                                state={document.state as EvidenceState}
                                            />
                                        )}
                                    </div>
                                    <p className="text-[13px]">
                                        {document.sharedWith.length === 0
                                            ? t('documents.not_shared')
                                            : t('documents.shared_with', {
                                                  jobs: document.sharedWith.join(', '),
                                              })}
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {document.fileUrl && (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="min-h-11"
                                            >
                                                <a
                                                    href={document.fileUrl}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    {t('documents.open')}
                                                </a>
                                            </Button>
                                        )}
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="min-h-11 text-bad"
                                                >
                                                    <Trash2 aria-hidden className="size-4" />
                                                    {t('documents.remove')}
                                                </Button>
                                            }
                                            title={t('documents.remove_title')}
                                            description={t('documents.remove_body')}
                                            confirmLabel={t('documents.remove')}
                                            destructive
                                            onConfirm={() =>
                                                router.delete(
                                                    worker.documents.destroy.url(document.id),
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <form
                    onSubmit={submit}
                    noValidate
                    aria-labelledby="upload-heading"
                    className="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-5"
                >
                    <h2 id="upload-heading" className="font-semibold">
                        {t('documents.upload_title')}
                    </h2>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="document-type">{t('documents.type')}</Label>
                        <NativeSelect
                            id="document-type"
                            value={type}
                            onChange={(event) => setType(event.target.value as DocumentType)}
                        >
                            {types.map((value) => (
                                <option key={value} value={value}>
                                    {t(`document_type.${value}`)}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.type} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="document-expires">
                            {t('documents.expires_label')} ({t('common.optional')})
                        </Label>
                        <Input
                            id="document-expires"
                            type="date"
                            value={expiresOn}
                            onChange={(event) => setExpiresOn(event.target.value)}
                            className="h-11 max-w-56 text-base"
                        />
                        <InputError message={errors.expires_on} />
                    </div>
                    <FileUpload
                        label={t('documents.file')}
                        value={file}
                        onChange={(selected) => setFile(selected)}
                        accept={['application/pdf', 'image/jpeg', 'image/png']}
                        acceptLabel="PDF, JPG, PNG"
                        maxSizeMb={maxSizeMb}
                        error={errors.document}
                        sensitive
                    />
                    {replacing && (
                        <p className="rounded-lg bg-warn-soft px-3 py-2 text-xs">
                            {t('documents.replace_note')}
                        </p>
                    )}
                    <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                        {t('documents.privacy_note')}
                    </p>
                    <Button
                        type="submit"
                        className="min-h-12 self-start"
                        disabled={processing || !file}
                    >
                        {processing && <Spinner />}
                        {t('documents.upload')}
                    </Button>
                </form>
            </div>
        </>
    );
}
