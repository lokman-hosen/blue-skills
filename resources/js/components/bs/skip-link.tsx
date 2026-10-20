import { useTranslation } from '@/lib/i18n';

export function SkipLink() {
    const { t } = useTranslation();

    return (
        <a
            href="#main"
            className="sr-only z-50 rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand focus:not-sr-only focus:fixed focus:start-3 focus:top-3"
        >
            {t('common.skip_to_content')}
        </a>
    );
}
