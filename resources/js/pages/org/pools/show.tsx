import { Head, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Eye, EyeOff, Pencil, Trash2, UserPlus, Users, Video } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { PoolFields } from '@/components/bs/institute/pool-fields';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Pool = App.Domain.Institute.Data.TalentPoolData;
type Member = App.Domain.Institute.Data.PoolMemberData;

type Props = {
    pool: Pool;
    members: Member[];
    available: Member[];
    occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
};

/** One talent pool: its graduates and whether employers can browse it (D-082). */
export default function PoolShow({ pool, members, available, occupations }: Props) {
    const { t, formatNumber } = useTranslation();
    const published = pool.publishedAt !== null;

    return (
        <>
            <Head title={pool.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={pool.name}
                    description={pool.description ?? undefined}
                    back={{ href: org.pools.index(), label: t('nav.talent_pools') }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip
                                tone={published ? 'ok' : 'neutral'}
                                label={published ? t('pools.published') : t('pools.private')}
                            />
                            <EditPool pool={pool} occupations={occupations} />
                            <Button
                                variant={published ? 'outline' : 'default'}
                                className="min-h-11"
                                onClick={() =>
                                    router.post(
                                        org.pools.publish.url([
                                            pool.id,
                                            published ? 'unpublish' : 'publish',
                                        ]),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {published ? (
                                    <EyeOff aria-hidden className="size-4" />
                                ) : (
                                    <Eye aria-hidden className="size-4" />
                                )}
                                {published ? t('pools.unpublish') : t('pools.publish')}
                            </Button>
                        </div>
                    }
                />
                <p className="rounded-xl bg-surface-2 px-4 py-3 text-sm">
                    {published
                        ? t('pools.published_note', {
                              visible: formatNumber(pool.visibleMembers),
                              members: formatNumber(pool.members),
                          })
                        : t('pools.private_note')}
                </p>

                <section className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="eyebrow text-muted">{t('pools.members_title')}</h2>
                        <AddMembers pool={pool} available={available} />
                    </div>
                    <DataTable<Member>
                        caption={t('pools.members_title')}
                        rows={members}
                        rowKey={(row) => row.profileId}
                        empty={
                            <EmptyState
                                icon={Users}
                                title={t('pools.no_members')}
                                description={t('pools.no_members_body')}
                            />
                        }
                        columns={[
                            {
                                key: 'name',
                                header: t('cohorts.column.graduate'),
                                primary: true,
                                cell: (row) => <MemberName member={row} />,
                            },
                            {
                                key: 'visible',
                                header: t('pools.column.visible'),
                                cell: (row) =>
                                    row.visibleToEmployers ? (
                                        t('common.yes')
                                    ) : (
                                        <span className="text-muted">{t('pools.not_visible')}</span>
                                    ),
                            },
                            {
                                key: 'evidence',
                                header: t('pools.column.evidence'),
                                cell: (row) => <MemberEvidence member={row} />,
                            },
                            {
                                key: 'remove',
                                header: t('cohorts.column.actions'),
                                cell: (row) => (
                                    <Button
                                        variant="ghost"
                                        className="min-h-11 text-bad"
                                        aria-label={t('pools.remove', { name: row.name })}
                                        onClick={() =>
                                            router.delete(
                                                org.pools.members.destroy.url([
                                                    pool.id,
                                                    row.profileId,
                                                ]),
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Trash2 aria-hidden className="size-4" />
                                    </Button>
                                ),
                            },
                        ]}
                    />
                </section>
            </div>
        </>
    );
}

function MemberName({ member }: { member: Member }) {
    return (
        <span className="font-semibold">
            {member.name}
            <span className="block font-mono text-xs font-normal text-muted" dir="ltr">
                {member.passportReference}
            </span>
            {member.occupation && (
                <span className="block text-xs font-normal text-muted">{member.occupation}</span>
            )}
        </span>
    );
}

function MemberEvidence({ member }: { member: Member }) {
    const { t } = useTranslation();

    return (
        <span className="flex flex-wrap gap-x-3 gap-y-1 text-sm">
            {member.attested && (
                <span className="inline-flex items-center gap-1">
                    <BadgeCheck aria-hidden className="size-4 text-ok" />
                    {t('status.record_attested')}
                </span>
            )}
            {member.hasSkillShow && (
                <span className="inline-flex items-center gap-1">
                    <Video aria-hidden className="size-4 text-brand" />
                    {t('nav.skillshow')}
                </span>
            )}
            {!member.attested && !member.hasSkillShow && <span className="text-muted">—</span>}
        </span>
    );
}

function AddMembers({ pool, available }: { pool: Pool; available: Member[] }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<string[]>([]);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            org.pools.members.store.url(pool.id),
            { profiles: selected },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(errors.profiles),
                onSuccess: () => {
                    setSelected([]);
                    setOpen(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="min-h-11" disabled={available.length === 0}>
                    <UserPlus aria-hidden className="size-4" />
                    {t('pools.add_members')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('pools.add_members')}</DialogTitle>
                    <DialogDescription>{t('pools.add_members_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <fieldset className="flex max-h-80 flex-col gap-1 overflow-y-auto">
                        <legend className="sr-only">{t('pools.add_members')}</legend>
                        {available.map((member) => (
                            <label
                                key={member.profileId}
                                className="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg px-2 hover:bg-surface-2"
                            >
                                <input
                                    type="checkbox"
                                    checked={selected.includes(member.profileId)}
                                    onChange={(event) =>
                                        setSelected((current) =>
                                            event.target.checked
                                                ? [...current, member.profileId]
                                                : current.filter((id) => id !== member.profileId),
                                        )
                                    }
                                    className="size-5 accent-[var(--color-brand)]"
                                />
                                <MemberName member={member} />
                            </label>
                        ))}
                    </fieldset>
                    <InputError message={error} />
                    <Button
                        type="submit"
                        className="min-h-11 self-end"
                        disabled={processing || selected.length === 0}
                    >
                        {processing && <Spinner />}
                        {t('pools.add_selected', { count: selected.length })}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditPool({
    pool,
    occupations,
}: {
    pool: Pool;
    occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: pool.name,
        description: pool.description ?? '',
        occupation_id: pool.occupationId ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(org.pools.update.url(pool.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="min-h-11">
                    <Pencil aria-hidden className="size-4" />
                    {t('common.edit')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('pools.edit_title')}</DialogTitle>
                    <DialogDescription>{t('pools.add_hint')}</DialogDescription>
                </DialogHeader>
                <PoolFields
                    form={form}
                    occupations={occupations}
                    id="pool-edit"
                    onSubmit={submit}
                />
            </DialogContent>
        </Dialog>
    );
}
