import { router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { NamesDialog } from './names-dialog';
import type { Locale } from './names-dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Item = App.Domain.Taxonomy.Data.TaxonomyItemData;

/** A taxonomy item with its names, how much uses it, rename, and remove when unused. */
export function TaxonomyItemRow({
    item,
    locales,
    title,
}: {
    item: Item;
    locales: Locale[];
    title?: ReactNode;
}) {
    const { t, formatNumber, locale } = useTranslation();
    const name = item.names[locale] || item.names.en;

    return (
        <li className="flex flex-wrap items-center gap-2 py-2">
            <span className="min-w-0 flex-1">
                {title ?? <span className="font-semibold">{name}</span>}
                <span className="block text-xs text-muted">
                    <span className="font-mono" dir="ltr">
                        {item.slug}
                    </span>
                    {' · '}
                    {t('taxonomy.usage', { count: formatNumber(item.usage) })}
                    {locales
                        .filter((language) => language.code !== 'en' && !item.names[language.code])
                        .map(
                            (language) =>
                                ` · ${t('taxonomy.missing', { language: language.name })}`,
                        )}
                </span>
            </span>
            <NamesDialog
                trigger={
                    <Button
                        variant="ghost"
                        className="size-11"
                        aria-label={t('taxonomy.rename', { name })}
                    >
                        <Pencil aria-hidden className="size-4" />
                    </Button>
                }
                title={t('taxonomy.rename', { name })}
                locales={locales}
                type={item.type}
                item={item}
            />
            {item.usage === 0 && (
                <Button
                    variant="ghost"
                    className="size-11 text-bad"
                    aria-label={t('taxonomy.remove', { name })}
                    onClick={() =>
                        router.delete(admin.taxonomy.destroy.url([item.type, item.id]), {
                            preserveScroll: true,
                        })
                    }
                >
                    <Trash2 aria-hidden className="size-4" />
                </Button>
            )}
        </li>
    );
}
