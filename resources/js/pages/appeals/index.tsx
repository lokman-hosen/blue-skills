import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import appeals from '@/routes/appeals';

type Suspension = App.Domain.Trust.Data.TrustActionData;

/** Suspensions affecting you, why, and your appeal (spec §10, D-088). */
export default function Appeals({ suspensions }: { suspensions: Suspension[] }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('appeals.title')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader title={t('appeals.title')} description={t('appeals.body')} />
                {suspensions.length === 0 ? (
                    <EmptyState
                        icon={ShieldCheck}
                        title={t('appeals.empty_title')}
                        description={t('appeals.empty_body')}
                    />
                ) : (
                    suspensions.map((suspension) => (
                        <SuspensionCard key={suspension.id} suspension={suspension} />
                    ))
                )}
            </div>
        </>
    );
}

function SuspensionCard({ suspension }: { suspension: Suspension }) {
    const { t, formatDate } = useTranslation();
    const pending = suspension.appeals.some((appeal) => appeal.status.value === 'appeal_submitted');
    const form = useForm({ statement: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(appeals.store.url(suspension.id), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <section className="flex flex-col gap-3 rounded-2xl border border-bad/40 bg-surface p-5">
            <div className="flex flex-col gap-1">
                <p className="font-semibold">
                    {t('appeals.suspended', { subject: suspension.subject })}
                </p>
                <p className="text-sm">
                    {t('appeals.reason', { reason: t(`trust_reason.${suspension.reason}`) })} ·{' '}
                    {formatDate(suspension.at)}
                </p>
                <p className="text-xs text-muted">{t('appeals.what_happens')}</p>
            </div>
            {suspension.appeals.map((appeal) => (
                <div
                    key={appeal.id}
                    className="flex flex-col gap-1 rounded-lg bg-surface-2 p-3 text-sm"
                >
                    <span className="flex flex-wrap items-center justify-between gap-2">
                        <span className="text-xs text-muted">
                            {t('appeals.sent_on', { date: formatDate(appeal.submittedAt) })}
                        </span>
                        <StatusChip status={appeal.status} />
                    </span>
                    <span className="whitespace-pre-line">{appeal.statement}</span>
                    {appeal.decisionNote && (
                        <span className="text-xs">
                            {t('appeals.decision', { note: appeal.decisionNote })}
                        </span>
                    )}
                </div>
            ))}
            {!pending && (
                <form onSubmit={submit} className="flex flex-col gap-2" noValidate>
                    <Label htmlFor={`statement-${suspension.id}`}>{t('appeals.statement')}</Label>
                    <p id={`statement-hint-${suspension.id}`} className="text-xs text-muted">
                        {t('appeals.statement_hint')}
                    </p>
                    <textarea
                        id={`statement-${suspension.id}`}
                        rows={5}
                        maxLength={3000}
                        value={form.data.statement}
                        aria-describedby={`statement-hint-${suspension.id}`}
                        onChange={(event) => form.setData('statement', event.target.value)}
                        className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                    />
                    <InputError message={form.errors.statement} />
                    <Button
                        type="submit"
                        className="min-h-11 self-start"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {t('appeals.send')}
                    </Button>
                </form>
            )}
        </section>
    );
}
