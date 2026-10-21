import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { PageHeader } from '@/components/bs/page-header';
import { TaxonomyItemRow } from '@/components/bs/taxonomy/item-row';
import { NamesDialog } from '@/components/bs/taxonomy/names-dialog';
import type { Locale } from '@/components/bs/taxonomy/names-dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Item = App.Domain.Taxonomy.Data.TaxonomyItemData;

type Props = {
    occupation: Item;
    specializations: Item[];
    skills: Item[];
    locales: Locale[];
};

/** One occupation: its specializations and skills. */
export default function TaxonomyOccupation({
    occupation,
    specializations,
    skills,
    locales,
}: Props) {
    const { t, locale } = useTranslation();
    const name = occupation.names[locale] || occupation.names.en;

    const list = (type: 'specialization' | 'skill', items: Item[]) => (
        <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4">
            <h2 className="eyebrow text-muted">{t(`taxonomy.${type}s`)}</h2>
            {items.length === 0 ? (
                <p className="text-sm text-muted">{t('taxonomy.none')}</p>
            ) : (
                <ul className="flex flex-col divide-y divide-line">
                    {items.map((item) => (
                        <TaxonomyItemRow key={item.id} item={item} locales={locales} />
                    ))}
                </ul>
            )}
            <NamesDialog
                trigger={
                    <Button variant="outline" className="min-h-11 self-start">
                        <Plus aria-hidden className="size-4" />
                        {t(`taxonomy.add.${type}`)}
                    </Button>
                }
                title={t(`taxonomy.add.${type}`)}
                locales={locales}
                type={type}
                parentId={occupation.id}
            />
        </section>
    );

    return (
        <>
            <Head title={name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={name}
                    description={t('taxonomy.occupation_body')}
                    back={{ href: admin.taxonomy.index(), label: t('nav.taxonomy') }}
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    {list('specialization', specializations)}
                    {list('skill', skills)}
                </div>
            </div>
        </>
    );
}
