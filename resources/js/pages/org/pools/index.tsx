import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Star } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { PoolFields } from '@/components/bs/institute/pool-fields';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Pool = App.Domain.Institute.Data.TalentPoolData;

type Props = {
    pools: Pool[];
    occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
    instituteVerified: boolean;
};

/** The institute's talent pools of graduates who accepted (spec §8, D-082). */
export default function Pools({ pools, occupations, instituteVerified }: Props) {
    const { t, formatNumber } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', description: '', occupation_id: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.pools.store.url(), { onSuccess: () => setOpen(false) });
    };

    const create = (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="min-h-11">
                    <Plus aria-hidden className="size-4" />
                    {t('pools.add')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('pools.add')}</DialogTitle>
                    <DialogDescription>{t('pools.add_hint')}</DialogDescription>
                </DialogHeader>
                <PoolFields form={form} occupations={occupations} id="pool-new" onSubmit={submit} />
            </DialogContent>
        </Dialog>
    );

    return (
        <>
            <Head title={t('nav.talent_pools')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.talent_pools')}
                    description={t('pools.body')}
                    actions={create}
                />
                {!instituteVerified && (
                    <p className="rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm">
                        {t('pools.needs_verification')}
                    </p>
                )}
                {pools.length === 0 ? (
                    <EmptyState
                        icon={Star}
                        title={t('pools.empty_title')}
                        description={t('pools.empty_body')}
                        action={create}
                    />
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2">
                        {pools.map((pool) => (
                            <li key={pool.id}>
                                <Link
                                    href={org.pools.show(pool.id)}
                                    className="flex h-full flex-col gap-2 rounded-2xl border border-line bg-surface p-4 hover:border-brand/60 sm:p-5"
                                >
                                    <span className="flex items-start justify-between gap-2">
                                        <span className="min-w-0 text-lg font-semibold break-words text-brand">
                                            {pool.name}
                                        </span>
                                        <StatusChip
                                            tone={pool.publishedAt ? 'ok' : 'neutral'}
                                            label={
                                                pool.publishedAt
                                                    ? t('pools.published')
                                                    : t('pools.private')
                                            }
                                        />
                                    </span>
                                    {pool.occupation && (
                                        <span className="text-sm text-muted">
                                            {pool.occupation}
                                        </span>
                                    )}
                                    <span className="text-sm">
                                        {t('pools.member_counts', {
                                            members: formatNumber(pool.members),
                                            visible: formatNumber(pool.visibleMembers),
                                        })}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
