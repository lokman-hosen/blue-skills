import { Head, Link, useForm } from '@inertiajs/react';
import { Star } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { SkillShowPlayer } from '@/components/bs/skill-show-player';
import { StatusChip } from '@/components/bs/status-chip';
import { WorkerProjection } from '@/components/bs/worker-projection';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    profile: App.Domain.Applications.Data.TalentProfileData;
    applications: { id: string; jobCode: string; jobTitle: string }[];
    shortlists: App.Domain.Applications.Data.ShortlistData[] | null;
    onShortlists: string[];
    canInvite: boolean;
    openJobs: { code: string; title: string }[];
    invitations: App.Domain.Applications.Data.JobInvitationData[];
};

/**
 * A discoverable worker's professional projection (product rule 1). Contact happens through an
 * application; phone numbers and documents are never shown here (product rule 6).
 */
export default function TalentShow({
    profile,
    applications,
    shortlists,
    onShortlists,
    canInvite,
    openJobs,
    invitations,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={profile.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={profile.reference}
                    title={profile.name}
                    back={{ href: org.talent.index(), label: t('nav.talent') }}
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,340px)] lg:items-start">
                    <div className="flex min-w-0 flex-col gap-5">
                        <Panel title={t('talent.profile')}>
                            <WorkerProjection profile={profile} />
                        </Panel>
                        <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                            {t('talent.contact_note')}
                        </p>
                    </div>
                    <aside className="flex flex-col gap-4 lg:sticky lg:top-20">
                        {profile.skillShow && (
                            <Panel title={t('nav.skillshow')}>
                                <SkillShowPlayer skillShow={profile.skillShow} />
                            </Panel>
                        )}
                        {(canInvite || invitations.length > 0) && (
                            <InvitePanel
                                reference={profile.reference}
                                canInvite={canInvite}
                                openJobs={openJobs}
                                invitations={invitations}
                            />
                        )}
                        {shortlists && (
                            <ShortlistPanel
                                reference={profile.reference}
                                shortlists={shortlists}
                                onShortlists={onShortlists}
                            />
                        )}
                        {applications.length > 0 && (
                            <Panel title={t('talent.applied_to')}>
                                <ul className="flex flex-col gap-1.5 text-sm">
                                    {applications.map((application) => (
                                        <li key={application.id}>
                                            <Link
                                                href={org.applicants.show(application.id)}
                                                className="inline-flex min-h-11 items-center font-semibold text-brand hover:underline"
                                            >
                                                {application.jobTitle} · {application.jobCode}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function ShortlistPanel({
    reference,
    shortlists,
    onShortlists,
}: {
    reference: string;
    shortlists: App.Domain.Applications.Data.ShortlistData[];
    onShortlists: string[];
}) {
    const { t } = useTranslation();
    const available = shortlists.filter((shortlist) => !onShortlists.includes(shortlist.id));
    const add = useForm({ list: available[0]?.id ?? '', worker: reference, note: '' });
    const create = useForm({ name: '', stay: true });

    const submitAdd = (event: FormEvent) => {
        event.preventDefault();
        add.transform(({ worker, note }) => ({ worker, note }));
        add.post(org.shortlists.entries.store.url(add.data.list), {
            preserveScroll: true,
            onSuccess: () => add.setData('note', ''),
        });
    };

    const submitCreate = (event: FormEvent) => {
        event.preventDefault();
        create.post(org.shortlists.store.url(), {
            preserveScroll: true,
            onSuccess: () => create.reset(),
        });
    };

    return (
        <Panel title={t('nav.shortlists')}>
            {onShortlists.length > 0 && (
                <p className="flex flex-wrap items-center gap-1.5 text-sm">
                    <Star aria-hidden className="size-4 text-stamp" />
                    {t('talent.on_lists', {
                        lists: shortlists
                            .filter((shortlist) => onShortlists.includes(shortlist.id))
                            .map((shortlist) => shortlist.name)
                            .join(', '),
                    })}
                </p>
            )}
            {available.length > 0 ? (
                <form onSubmit={submitAdd} className="flex flex-col gap-2" noValidate>
                    <Label htmlFor="shortlist-list">{t('talent.add_to')}</Label>
                    <NativeSelect
                        id="shortlist-list"
                        value={add.data.list}
                        onChange={(event) => add.setData('list', event.target.value)}
                    >
                        {available.map((shortlist) => (
                            <option key={shortlist.id} value={shortlist.id}>
                                {shortlist.name}
                            </option>
                        ))}
                    </NativeSelect>
                    <Label htmlFor="shortlist-note" className="text-xs">
                        {t('shortlists.note')} ({t('common.optional')})
                    </Label>
                    <Input
                        id="shortlist-note"
                        className="h-11"
                        value={add.data.note}
                        maxLength={500}
                        onChange={(event) => add.setData('note', event.target.value)}
                    />
                    <InputError message={add.errors.worker ?? add.errors.note} />
                    <Button type="submit" className="min-h-11" disabled={add.processing}>
                        {add.processing && <Spinner />}
                        {t('talent.add')}
                    </Button>
                </form>
            ) : (
                <form onSubmit={submitCreate} className="flex flex-col gap-2" noValidate>
                    <Label htmlFor="shortlist-new">{t('shortlists.new')}</Label>
                    <Input
                        id="shortlist-new"
                        className="h-11"
                        value={create.data.name}
                        maxLength={120}
                        onChange={(event) => create.setData('name', event.target.value)}
                        placeholder={t('shortlists.name_placeholder')}
                    />
                    <InputError message={create.errors.name} />
                    <Button
                        type="submit"
                        variant="outline"
                        className="min-h-11"
                        disabled={create.processing || create.data.name.trim() === ''}
                    >
                        {t('shortlists.create')}
                    </Button>
                </form>
            )}
        </Panel>
    );
}

function InvitePanel({
    reference,
    canInvite,
    openJobs,
    invitations,
}: {
    reference: string;
    canInvite: boolean;
    openJobs: { code: string; title: string }[];
    invitations: App.Domain.Applications.Data.JobInvitationData[];
}) {
    const { t, formatDate } = useTranslation();
    const invited = invitations.map((invitation) => invitation.job.code);
    const available = openJobs.filter((job) => !invited.includes(job.code));
    const form = useForm({ job: available[0]?.code ?? '', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.talent.invite.url(reference), {
            preserveScroll: true,
            onSuccess: () => form.reset('note'),
        });
    };

    return (
        <Panel title={t('invitations.invite_title')}>
            {invitations.length > 0 && (
                <ul className="flex flex-col gap-1.5 text-sm">
                    {invitations.map((invitation) => (
                        <li
                            key={invitation.id}
                            className="flex flex-wrap items-center justify-between gap-2"
                        >
                            <span className="min-w-0">
                                {invitation.job.title}
                                <span className="block text-xs text-muted">
                                    {formatDate(invitation.sentAt)}
                                </span>
                            </span>
                            <StatusChip
                                icon={false}
                                label={t(`invitation_status.${invitation.status}`)}
                                tone={
                                    invitation.status === 'applied'
                                        ? 'ok'
                                        : invitation.status === 'sent'
                                          ? 'stamp'
                                          : 'neutral'
                                }
                            />
                        </li>
                    ))}
                </ul>
            )}
            {canInvite && available.length > 0 && (
                <form onSubmit={submit} className="flex flex-col gap-2" noValidate>
                    <Label htmlFor="invite-job">{t('invitations.job')}</Label>
                    <NativeSelect
                        id="invite-job"
                        value={form.data.job}
                        onChange={(event) => form.setData('job', event.target.value)}
                    >
                        {available.map((job) => (
                            <option key={job.code} value={job.code}>
                                {job.title} · {job.code}
                            </option>
                        ))}
                    </NativeSelect>
                    <Label htmlFor="invite-note" className="text-xs">
                        {t('invitations.note')} ({t('common.optional')})
                    </Label>
                    <textarea
                        id="invite-note"
                        rows={2}
                        maxLength={300}
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                    />
                    <InputError message={form.errors.job ?? form.errors.note} />
                    <p className="text-xs text-muted">{t('invitations.org_note')}</p>
                    <Button type="submit" className="min-h-11" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {t('invitations.send')}
                    </Button>
                </form>
            )}
            {canInvite && available.length === 0 && invitations.length === 0 && (
                <p className="text-sm text-muted">{t('invitations.no_open_jobs')}</p>
            )}
        </Panel>
    );
}
