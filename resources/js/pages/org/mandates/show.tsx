import { Head, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { FileUpload } from '@/components/bs/file-upload';
import { MandateSummary } from '@/components/bs/mandate-summary';
import { PageHeader } from '@/components/bs/page-header';
import { ReviewHistory } from '@/components/bs/review-history';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    mandate: App.Domain.Jobs.Data.MandateData;
    side: 'agency' | 'employer';
    abilities: { confirm: boolean; addEvidence: boolean };
};

/** One mandate: scope, letter, employer answer and staff decision. */
export default function MandateShow({ mandate, side, abilities }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={mandate.reference} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <PageHeader
                    eyebrow={mandate.reference}
                    title={side === 'agency' ? mandate.employer : mandate.agency}
                    description={t(`mandates.status_note.${mandate.status.value}`, {}, '')}
                    back={{ href: org.mandates.index(), label: t('nav.mandates') }}
                />
                <MandateSummary mandate={mandate} />
                {abilities.confirm && <RespondPanel mandate={mandate} />}
                {abilities.addEvidence && <EvidencePanel mandate={mandate} />}
                {mandate.lastDecision && <ReviewHistory history={[mandate.lastDecision]} />}
            </div>
        </>
    );
}

function RespondPanel({ mandate }: { mandate: App.Domain.Jobs.Data.MandateData }) {
    const { t } = useTranslation();
    const form = useForm({ note: '' });
    const [declining, setDeclining] = useState(false);

    const submit = (answer: 'confirm' | 'decline') => (event: FormEvent) => {
        event.preventDefault();
        form.post(org.mandates.respond.url([mandate.id, answer]), { preserveScroll: true });
    };

    return (
        <section className="flex flex-col gap-3 rounded-2xl border-2 border-brand/50 bg-surface p-5">
            <h2 className="font-semibold">
                {t('mandates.confirm_title', { agency: mandate.agency })}
            </h2>
            <p className="text-sm">{t('mandates.confirm_body')}</p>
            <form
                onSubmit={submit(declining ? 'decline' : 'confirm')}
                className="flex flex-col gap-3"
                noValidate
            >
                <Label htmlFor="mandate-note">
                    {t('mandates.note')} {declining ? '' : `(${t('common.optional')})`}
                </Label>
                <textarea
                    id="mandate-note"
                    rows={2}
                    maxLength={500}
                    value={form.data.note}
                    onChange={(event) => form.setData('note', event.target.value)}
                    className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                />
                <InputError
                    message={form.errors.note ?? (form.errors as Record<string, string>).mandate}
                />
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="submit"
                        className="min-h-11"
                        disabled={form.processing}
                        onClick={() => setDeclining(false)}
                    >
                        {form.processing && !declining && <Spinner />}
                        {t('mandates.confirm')}
                    </Button>
                    <Button
                        type="submit"
                        variant="outline"
                        className="min-h-11 text-bad"
                        disabled={form.processing}
                        onClick={() => setDeclining(true)}
                    >
                        {t('mandates.decline')}
                    </Button>
                </div>
            </form>
        </section>
    );
}

function EvidencePanel({ mandate }: { mandate: App.Domain.Jobs.Data.MandateData }) {
    const { t } = useTranslation();
    const [file, setFile] = useState<File | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);

    return (
        <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5">
            <h2 className="font-semibold">{t('mandates.add_evidence')}</h2>
            <FileUpload
                label={t('mandates.letter')}
                value={file}
                onChange={setFile}
                accept={['application/pdf', 'image/jpeg', 'image/png']}
                acceptLabel="PDF, JPG, PNG"
                maxSizeMb={10}
                error={error}
                sensitive
            />
            <Button
                className="min-h-11 self-start"
                disabled={!file || processing}
                onClick={() =>
                    router.post(
                        org.mandates.evidence.url(mandate.id),
                        { document: file },
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
                {processing && <Spinner />}
                {t('mandates.upload')}
            </Button>
        </section>
    );
}
