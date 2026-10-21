import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

export type Locale = { code: string; name: string };
type Item = App.Domain.Taxonomy.Data.TaxonomyItemData;

/**
 * Add or rename a taxonomy item with its name in each launch language (D-093). English is
 * required; other languages fall back to it.
 */
export function NamesDialog({
    trigger,
    title,
    locales,
    type,
    parentId,
    item,
}: {
    trigger: ReactNode;
    title: string;
    locales: Locale[];
    type: string;
    parentId?: string;
    item?: Item;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        type,
        parent_id: parentId ?? '',
        names: Object.fromEntries(
            locales.map((locale) => [locale.code, item?.names[locale.code] ?? '']),
        ) as Record<string, string>,
    });
    const errors = form.errors as Record<string, string>;
    const id = item ? `rename-${item.id}` : `add-${type}-${parentId ?? 'root'}`;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                if (!item) {
                    form.reset();
                }
            },
        };

        if (item) {
            form.put(admin.taxonomy.update.url([item.type, item.id]), options);
        } else {
            form.post(admin.taxonomy.store.url(), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{t('taxonomy.names_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    {locales.map((locale) => (
                        <div key={locale.code} className="flex flex-col gap-1.5">
                            <Label htmlFor={`${id}-${locale.code}`}>
                                {locale.name}
                                {locale.code !== 'en' && ` (${t('common.optional')})`}
                            </Label>
                            <Input
                                id={`${id}-${locale.code}`}
                                lang={locale.code}
                                className="h-11"
                                maxLength={120}
                                value={form.data.names[locale.code] ?? ''}
                                onChange={(event) =>
                                    form.setData('names', {
                                        ...form.data.names,
                                        [locale.code]: event.target.value,
                                    })
                                }
                            />
                            <InputError message={errors[`names.${locale.code}`]} />
                        </div>
                    ))}
                    <InputError message={errors.parent_id ?? errors.type} />
                    <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {t('common.save')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
