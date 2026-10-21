import { Form, Head, router } from '@inertiajs/react';
import { ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PrivacyController from '@/actions/App/Http/Controllers/Worker/PrivacyController';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import worker from '@/routes/worker';

type Visibility = App.Domain.Worker.Enums.ProfileVisibility;

type Props = {
    visibility: Visibility;
    consents: App.Domain.Worker.Data.ConsentRecordData[];
    institutes: App.Domain.Institute.Data.WorkerCohortData[];
    deletionCodeSent: boolean;
    maskedPhone: string;
    codeLength: number;
};

const OPTIONS: Visibility[] = ['employers_and_agencies', 'applications_only', 'hidden'];

/** Visibility, consent history and account deletion (spec §3, D-039, D-044). */
export default function Privacy({
    visibility,
    consents,
    institutes,
    deletionCodeSent,
    maskedPhone,
    codeLength,
}: Props) {
    const { t, formatDate } = useTranslation();
    const [choice, setChoice] = useState<Visibility>(visibility);
    const [saving, setSaving] = useState(false);

    return (
        <>
            <Head title={t('nav.privacy')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader title={t('nav.privacy')} description={t('privacy.body')} />

                <section
                    className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
                    aria-labelledby="visibility-heading"
                >
                    <h2 id="visibility-heading" className="font-semibold">
                        {t('privacy.visibility_title')}
                    </h2>
                    <fieldset className="flex flex-col gap-2">
                        <legend className="sr-only">{t('privacy.visibility_title')}</legend>
                        {OPTIONS.map((value) => (
                            <label
                                key={value}
                                className={cn(
                                    'flex cursor-pointer gap-3 rounded-xl border p-3.5 focus-within:ring-[3px] focus-within:ring-ring/50',
                                    choice === value
                                        ? 'border-brand bg-brand-soft'
                                        : 'border-line hover:border-brand/60',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="visibility"
                                    value={value}
                                    checked={choice === value}
                                    onChange={() => setChoice(value)}
                                    className="mt-1 size-4 accent-[var(--color-brand)]"
                                />
                                <span className="flex flex-col gap-0.5">
                                    <span className="text-sm font-semibold">
                                        {t(`visibility.${value}`)}
                                    </span>
                                    <span className="text-xs text-muted">
                                        {t(`privacy.visibility.${value}`)}
                                    </span>
                                </span>
                            </label>
                        ))}
                    </fieldset>
                    <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                        {t('privacy.never_shared')}
                    </p>
                    <Button
                        className="min-h-11 self-start"
                        disabled={choice === visibility || saving}
                        onClick={() =>
                            router.put(
                                PrivacyController.updateVisibility.url(),
                                { visibility: choice },
                                {
                                    preserveScroll: true,
                                    onStart: () => setSaving(true),
                                    onFinish: () => setSaving(false),
                                },
                            )
                        }
                    >
                        {saving && <Spinner />}
                        {t('common.save')}
                    </Button>
                </section>

                <MyInstitutes institutes={institutes} />

                <section
                    className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
                    aria-labelledby="consents-heading"
                >
                    <h2 id="consents-heading" className="font-semibold">
                        {t('privacy.consents_title')}
                    </h2>
                    {consents.length === 0 ? (
                        <EmptyState
                            icon={ShieldCheck}
                            title={t('privacy.consents_empty_title')}
                            description={t('privacy.consents_empty_body')}
                        />
                    ) : (
                        <ul className="flex flex-col divide-y divide-line">
                            {consents.map((consent) => (
                                <li
                                    key={consent.id}
                                    className="flex flex-wrap items-center justify-between gap-2 py-2.5"
                                >
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold">
                                            {t(`consent.purpose.${consent.purpose}`)}
                                        </p>
                                        <p className="text-xs text-muted">
                                            {t('privacy.granted_on', {
                                                date: formatDate(consent.grantedAt),
                                            })}
                                            {consent.revokedAt &&
                                                ` · ${t('privacy.revoked_on', { date: formatDate(consent.revokedAt) })}`}
                                        </p>
                                    </div>
                                    <StatusChip
                                        label={
                                            consent.revokedAt
                                                ? t('privacy.revoked')
                                                : t('privacy.active')
                                        }
                                        tone={consent.revokedAt ? 'neutral' : 'ok'}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section
                    className="flex flex-col gap-3 rounded-2xl border border-bad/40 bg-surface p-5"
                    aria-labelledby="delete-heading"
                >
                    <h2 id="delete-heading" className="font-semibold text-bad">
                        {t('privacy.delete.title')}
                    </h2>
                    <p className="text-sm text-muted">{t('privacy.delete.body')}</p>
                    {deletionCodeSent ? (
                        <Form
                            {...PrivacyController.destroy.form()}
                            className="flex flex-col gap-3"
                            resetOnError
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="deletion-code">
                                            {t('privacy.delete.code_label', { phone: maskedPhone })}
                                        </Label>
                                        <Input
                                            id="deletion-code"
                                            name="code"
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            maxLength={codeLength}
                                            className="h-11 max-w-48 font-mono text-base tracking-widest"
                                            dir="ltr"
                                            aria-invalid={!!errors.code}
                                        />
                                        <InputError message={errors.code} />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        className="min-h-11 self-start"
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <Spinner />
                                        ) : (
                                            <Trash2 aria-hidden className="size-4" />
                                        )}
                                        {t('privacy.delete.confirm')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    ) : (
                        <Form
                            {...PrivacyController.sendDeletionCode.form()}
                            className="flex flex-col gap-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.code} />
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        className="min-h-11 self-start text-bad"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        {t('privacy.delete.send_code', { phone: maskedPhone })}
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </section>
            </div>
        </>
    );
}

/** Institutes the worker joined: share progress or leave (product rule 11, D-082). */
function MyInstitutes({
    institutes,
}: {
    institutes: App.Domain.Institute.Data.WorkerCohortData[];
}) {
    const { t, formatDate } = useTranslation();
    const [busy, setBusy] = useState<string | null>(null);
    const options = (id: string) => ({
        preserveScroll: true,
        onStart: () => setBusy(id),
        onFinish: () => setBusy(null),
    });

    return (
        <section
            id="institutes"
            className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5"
            aria-labelledby="institutes-heading"
        >
            <h2 id="institutes-heading" className="font-semibold">
                {t('privacy.institutes.title')}
            </h2>
            <p className="text-sm text-muted">{t('privacy.institutes.body')}</p>
            {institutes.length === 0 ? (
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-sm text-muted">
                    {t('privacy.institutes.empty')}
                </p>
            ) : (
                <ul className="flex flex-col divide-y divide-line">
                    {institutes.map((link) => (
                        <li key={link.id} className="flex flex-col gap-2 py-3">
                            <div className="flex flex-col gap-0.5">
                                <p className="font-semibold">{link.institute}</p>
                                <p className="text-xs text-muted">
                                    {[
                                        link.course,
                                        link.cohort,
                                        link.joinedAt &&
                                            t('privacy.institutes.joined', {
                                                date: formatDate(link.joinedAt),
                                            }),
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                            </div>
                            <div className="flex min-h-11 items-start gap-2">
                                <Checkbox
                                    id={`share-${link.id}`}
                                    checked={link.sharesProgress}
                                    disabled={busy === link.id}
                                    onCheckedChange={(checked) =>
                                        router.put(
                                            worker.institutes.sharing.url(link.id),
                                            { shares_progress: checked === true },
                                            options(link.id),
                                        )
                                    }
                                    className="mt-0.5"
                                />
                                <Label
                                    htmlFor={`share-${link.id}`}
                                    className="flex flex-col items-start gap-0.5 font-normal"
                                >
                                    <span className="text-sm">
                                        {t('privacy.institutes.share', {
                                            institute: link.institute,
                                        })}
                                    </span>
                                    <span className="text-xs text-muted">
                                        {t('privacy.institutes.share_hint')}
                                    </span>
                                </Label>
                            </div>
                            <Button
                                variant="ghost"
                                className="min-h-11 self-start text-bad"
                                disabled={busy === link.id}
                                onClick={() =>
                                    router.post(
                                        worker.institutes.leave.url(link.id),
                                        {},
                                        options(link.id),
                                    )
                                }
                            >
                                {t('privacy.institutes.leave')}
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
