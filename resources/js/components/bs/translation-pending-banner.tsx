import { usePage } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';

/** Shown while the current locale's strings await human review (si, ta at launch). */
export function TranslationPendingBanner() {
    const { t, locale } = useTranslation();
    const { locale: shared, locales } = usePage().props;

    if (!shared.translationPending) {
        return null;
    }

    const language = locales.find((option) => option.code === locale);

    return (
        <div role="status" className="bg-stamp-soft px-4 py-1.5 text-[11.5px] text-stamp">
            {t('language.pending', { language: language?.englishName ?? locale })}
        </div>
    );
}
