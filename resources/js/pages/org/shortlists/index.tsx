import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronRight, Star } from 'lucide-react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    shortlists: App.Domain.Applications.Data.ShortlistData[];
    jobs: { code: string; title: string }[];
};

/** The organization's shortlists (spec §14). Lists give no extra access to profiles. */
export default function ShortlistsIndex({ shortlists, jobs }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const form = useForm({ name: '', job: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.shortlists.store.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('nav.shortlists')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.shortlists')} description={t('shortlists.body')} />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,320px)] lg:items-start">
                    {shortlists.length === 0 ? (
                        <EmptyState
                            icon={Star}
                            title={t('shortlists.empty_title')}
                            description={t('shortlists.empty_body')}
                            action={
                                <Button asChild variant="outline" className="min-h-11">
                                    <Link href={org.talent.index()}>{t('nav.talent')}</Link>
                                </Button>
                            }
                        />
                    ) : (
                        <ul className="flex min-w-0 flex-col gap-3">
                            {shortlists.map((shortlist) => (
                                <li key={shortlist.id}>
                                    <Link
                                        href={org.shortlists.show(shortlist.id)}
                                        className="flex items-center gap-3 rounded-xl border border-line bg-surface p-4 hover:border-brand/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    >
                                        <span className="flex min-w-0 flex-1 flex-col">
                                            <span className="font-semibold">{shortlist.name}</span>
                                            <span className="text-xs text-muted">
                                                {[
                                                    t('shortlists.count', {
                                                        count: formatNumber(shortlist.entries),
                                                    }),
                                                    shortlist.jobTitle,
                                                    shortlist.createdBy,
                                                    formatDate(shortlist.createdAt),
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </span>
                                        </span>
                                        <ChevronRight
                                            aria-hidden
                                            className="size-4 text-muted rtl:rotate-180"
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                    <form
                        onSubmit={submit}
                        noValidate
                        aria-labelledby="new-shortlist"
                        className="flex flex-col gap-3 rounded-xl border border-line bg-surface p-4"
                    >
                        <h2 id="new-shortlist" className="font-semibold">
                            {t('shortlists.new')}
                        </h2>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="shortlist-name">{t('shortlists.name')}</Label>
                            <Input
                                id="shortlist-name"
                                className="h-11"
                                value={form.data.name}
                                maxLength={120}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder={t('shortlists.name_placeholder')}
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="shortlist-job">
                                {t('shortlists.for_job')} ({t('common.optional')})
                            </Label>
                            <NativeSelect
                                id="shortlist-job"
                                value={form.data.job}
                                onChange={(event) => form.setData('job', event.target.value)}
                            >
                                <option value="">{t('shortlists.no_job')}</option>
                                {jobs.map((job) => (
                                    <option key={job.code} value={job.code}>
                                        {job.title} · {job.code}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.job} />
                        </div>
                        <Button type="submit" className="min-h-11" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {t('shortlists.create')}
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}
