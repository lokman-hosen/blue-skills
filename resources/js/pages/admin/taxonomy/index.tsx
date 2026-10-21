import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { PageHeader } from '@/components/bs/page-header';
import { TaxonomyItemRow } from '@/components/bs/taxonomy/item-row';
import { NamesDialog } from '@/components/bs/taxonomy/names-dialog';
import type { Locale } from '@/components/bs/taxonomy/names-dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Item = App.Domain.Taxonomy.Data.TaxonomyItemData;

/** Sectors and their occupations (spec §11: Sector → Occupation → Specialization → Skill). */
export default function TaxonomyIndex({
    sectors,
    locales,
}: {
    sectors: Item[];
    locales: Locale[];
}) {
    const { t, locale } = useTranslation();

    return (
        <>
            <Head title={t('nav.taxonomy')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.taxonomy')}
                    description={t('taxonomy.body')}
                    actions={
                        <NamesDialog
                            trigger={
                                <Button className="min-h-11">
                                    <Plus aria-hidden className="size-4" />
                                    {t('taxonomy.add.sector')}
                                </Button>
                            }
                            title={t('taxonomy.add.sector')}
                            locales={locales}
                            type="sector"
                        />
                    }
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    {sectors.map((sector) => (
                        <section
                            key={sector.id}
                            className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-4"
                        >
                            <ul>
                                <TaxonomyItemRow
                                    item={sector}
                                    locales={locales}
                                    title={
                                        <span className="text-lg font-semibold">
                                            {sector.names[locale] || sector.names.en}
                                        </span>
                                    }
                                />
                            </ul>
                            <ul className="flex flex-col divide-y divide-line border-t border-line">
                                {sector.children.map((occupation) => (
                                    <TaxonomyItemRow
                                        key={occupation.id}
                                        item={occupation}
                                        locales={locales}
                                        title={
                                            <Link
                                                href={admin.taxonomy.occupations.show(
                                                    occupation.id,
                                                )}
                                                className="font-semibold text-brand hover:underline"
                                            >
                                                {occupation.names[locale] || occupation.names.en}
                                            </Link>
                                        }
                                    />
                                ))}
                            </ul>
                            <NamesDialog
                                trigger={
                                    <Button variant="outline" className="min-h-11 self-start">
                                        <Plus aria-hidden className="size-4" />
                                        {t('taxonomy.add.occupation')}
                                    </Button>
                                }
                                title={t('taxonomy.add.occupation')}
                                locales={locales}
                                type="occupation"
                                parentId={sector.id}
                            />
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}
