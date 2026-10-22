import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';

type Range = { from: string; to: string };

/** From/to dates for analytics pages; reloads the page with the new range. */
export function RangeForm({ range, url }: { range: Range; url: (range: Range) => string }) {
    const { t } = useTranslation();
    const [draft, setDraft] = useState<Range>(range);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(url(draft), {}, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3" noValidate>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="range-from">{t('analytics.from')}</Label>
                <Input
                    id="range-from"
                    type="date"
                    className="h-11 w-40"
                    value={draft.from}
                    max={draft.to}
                    onChange={(event) => setDraft({ ...draft, from: event.target.value })}
                />
            </div>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="range-to">{t('analytics.to')}</Label>
                <Input
                    id="range-to"
                    type="date"
                    className="h-11 w-40"
                    value={draft.to}
                    min={draft.from}
                    onChange={(event) => setDraft({ ...draft, to: event.target.value })}
                />
            </div>
            <Button type="submit" variant="outline" className="min-h-11">
                {t('analytics.apply')}
            </Button>
        </form>
    );
}
