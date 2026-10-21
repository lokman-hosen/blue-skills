import { Head, Link, useForm } from '@inertiajs/react';
import { CircleAlert, Lock } from 'lucide-react';
import type { FormEvent } from 'react';
import { ConsentToggle } from '@/components/bs/consent-toggle';
import { MatchSummary } from '@/components/bs/jobs/match-summary';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import jobs from '@/routes/jobs';
import worker from '@/routes/worker';

type Item = App.Domain.Applications.Data.ShareableItemData;
type ItemKey = App.Domain.Applications.Enums.ApplicationConsentItem;

type Props = {
    job: App.Domain.Jobs.Data.JobListingData;
    items: Item[];
    acceptsApplications: boolean;
    hasTrade: boolean;
};

/**
 * "Share with employer?" (deck slide 12): per-item consent, then a dated profile snapshot is
 * stored with the application (product rules 6 and 7).
 */
export default function Apply({ job, items, acceptsApplications, hasTrade }: Props) {
    const { t } = useTranslation();
    const form = useForm<{ items: ItemKey[] }>({
        items: items.filter((item) => item.sharedByDefault).map((item) => item.item),
    });

    const toggle = (key: ItemKey, on: boolean) =>
        form.setData(
            'items',
            on ? [...form.data.items, key] : form.data.items.filter((item) => item !== key),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(worker.apply.store.url(job.code), { preserveScroll: true });
    };

    const blocked = !acceptsApplications || !hasTrade;

    return (
        <>
            <Head title={t('applications.apply.title')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    title={t('applications.apply.title')}
                    description={t('applications.apply.body')}
                    back={{ href: jobs.show(job.code), label: job.title }}
                />

                <div className="rounded-xl bg-surface-2 p-3">
                    <p className="text-sm font-semibold">
                        {job.title}
                        {job.destinationCity && ` · ${job.destinationCity}`}
                    </p>
                    <p className="text-xs text-muted">
                        {job.employerName} ·{' '}
                        <span className="font-mono" dir="ltr">
                            {job.code}
                        </span>
                    </p>
                </div>

                {job.match && <MatchSummary match={job.match} />}

                {blocked ? (
                    <div
                        role="alert"
                        className="flex flex-col gap-3 rounded-xl border border-warn/40 bg-warn-soft p-4 text-sm"
                    >
                        <p className="flex items-start gap-2 font-semibold">
                            <CircleAlert aria-hidden className="mt-0.5 size-4 shrink-0 text-warn" />
                            {!acceptsApplications
                                ? t('applications.apply.closed')
                                : t('applications.apply.needs_trade')}
                        </p>
                        {acceptsApplications && (
                            <Button asChild className="min-h-11 self-start">
                                <Link href={worker.basics.edit()}>
                                    {t('applications.apply.add_trade')}
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                        <fieldset className="flex flex-col gap-2">
                            <legend className="mb-2 font-semibold">
                                {t('applications.apply.share_title')}
                            </legend>
                            {items.map((item) => (
                                <ConsentToggle
                                    key={item.item}
                                    label={t(`consent.item.${item.item}`)}
                                    description={t(
                                        `applications.item_detail.${item.detail}`,
                                        item.params,
                                    )}
                                    sensitive={item.sensitive}
                                    checked={item.required || form.data.items.includes(item.item)}
                                    disabled={item.required || !item.available}
                                    onCheckedChange={(on) => toggle(item.item, on)}
                                />
                            ))}
                        </fieldset>

                        {items.some((item) => item.item === 'passport_scan' && !item.available) && (
                            <p className="flex items-center gap-2 text-xs text-muted">
                                <Lock aria-hidden className="size-3.5" />
                                <span>
                                    {t('applications.apply.scan_hint')}{' '}
                                    <Link
                                        href={worker.documents.index()}
                                        className="font-semibold text-brand hover:underline"
                                    >
                                        {t('nav.documents')}
                                    </Link>
                                </span>
                            </p>
                        )}

                        <p className="rounded-lg border border-line px-3 py-2 text-xs text-muted">
                            {t('applications.apply.snapshot_note')}
                        </p>

                        <InputError
                            message={
                                (form.errors as Record<string, string | undefined>).job ??
                                form.errors.items
                            }
                        />

                        <Button
                            type="submit"
                            className="min-h-12 w-full text-base"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {t('applications.apply.confirm')}
                        </Button>
                    </form>
                )}
            </div>
        </>
    );
}
