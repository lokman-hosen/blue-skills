import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';
import VerificationEvidenceController from '@/actions/App/Http/Controllers/Organization/VerificationEvidenceController';
import { FileUpload } from '@/components/bs/file-upload';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';

type Props = {
    organization: App.Domain.Organization.Data.OrganizationSummaryData;
    documents: App.Domain.Organization.Data.EvidenceDocumentData[];
    reviewOpen: boolean;
    lastDecision: App.Domain.Verification.Data.ReviewDecisionData | null;
    canResubmit: boolean;
    maxSizeMb: number;
};

/** Registration evidence for staff review (spec §1, D-036). */
export default function VerificationEvidence({
    organization,
    documents,
    reviewOpen,
    lastDecision,
    canResubmit,
    maxSizeMb,
}: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [uploading, setUploading] = useState(false);

    setLayoutProps({
        title: t('onboarding.evidence.title'),
        description: t('onboarding.evidence.body'),
        wide: true,
    });

    const upload = () => {
        if (!file) {
            return;
        }

        router.post(
            VerificationEvidenceController.store.url(),
            { document: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setUploading(true),
                onFinish: () => setUploading(false),
                onError: (errors) => setError(errors.document),
                onSuccess: () => {
                    setFile(null);
                    setError(undefined);
                },
            },
        );
    };

    return (
        <>
            <Head title={t('onboarding.evidence.title')} />
            <p className="mb-4 text-center eyebrow text-brand">
                {t('onboarding.step', { step: 3, total: 3 })}
            </p>
            <div className="flex flex-col gap-5">
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-surface-2 p-3">
                    <div className="min-w-0">
                        <p className="truncate font-semibold">{organization.displayName}</p>
                        <p className="text-xs text-muted">{t(`org.type.${organization.type}`)}</p>
                    </div>
                    <StatusChip status={organization.verification} />
                </div>

                {lastDecision && lastDecision.decision !== 'approved' && (
                    <div
                        role="status"
                        className="flex flex-col gap-1 rounded-xl border border-warn/30 bg-warn-soft px-4 py-3 text-sm"
                    >
                        <p className="font-semibold">
                            {t(`onboarding.evidence.decision.${lastDecision.decision}`)}
                        </p>
                        {lastDecision.reasonCode && (
                            <p>{t(`review_reason.${lastDecision.reasonCode}`)}</p>
                        )}
                        {lastDecision.note && (
                            <p className="text-muted" dir="auto">
                                “{lastDecision.note}”
                            </p>
                        )}
                    </div>
                )}

                {reviewOpen ? (
                    <>
                        <div className="flex flex-col gap-3">
                            <FileUpload
                                label={t(`onboarding.evidence.document.${organization.type}`)}
                                value={file}
                                onChange={(selected) => {
                                    setFile(selected);
                                    setError(undefined);
                                }}
                                accept={['application/pdf', 'image/jpeg', 'image/png']}
                                acceptLabel="PDF, JPG, PNG"
                                maxSizeMb={maxSizeMb}
                                error={error}
                                sensitive
                            />
                            <Button
                                onClick={upload}
                                disabled={!file || uploading}
                                className="min-h-11 self-start"
                            >
                                {uploading && <Spinner />}
                                {t('onboarding.evidence.upload')}
                            </Button>
                        </div>

                        {documents.length > 0 && (
                            <div>
                                <p className="mb-2 eyebrow text-muted">
                                    {t('onboarding.evidence.uploaded')}
                                </p>
                                <ul className="flex flex-col gap-2">
                                    {documents.map((document) => (
                                        <li
                                            key={document.id}
                                            className="flex items-center gap-3 rounded-xl border border-line p-3"
                                        >
                                            <FileText
                                                aria-hidden
                                                className="size-5 shrink-0 text-brand"
                                            />
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-semibold">
                                                    {document.name}
                                                </span>
                                                <span className="block text-xs text-muted">
                                                    {formatNumber(document.sizeKb)} KB
                                                    {document.uploadedAt &&
                                                        ` · ${formatDate(document.uploadedAt)}`}
                                                </span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex flex-col gap-3">
                        <p className="text-sm text-muted">{t('onboarding.evidence.no_review')}</p>
                        {canResubmit && (
                            <Form {...VerificationEvidenceController.resubmit.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        className="min-h-11"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        {t('onboarding.evidence.resubmit')}
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                )}

                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('onboarding.evidence.next')}
                </p>
                <Button
                    asChild
                    variant={documents.length > 0 ? 'default' : 'outline'}
                    className="min-h-12"
                >
                    <Link href={dashboard()}>
                        {documents.length > 0
                            ? t('onboarding.evidence.done')
                            : t('onboarding.evidence.later')}
                    </Link>
                </Button>
            </div>
        </>
    );
}
