import { usePage } from '@inertiajs/react';
import { FlaskConical } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';

/** Shown on every page while BLUESKILLS runs as a pilot with selected partners (D-108). */
export function PilotBanner() {
    const { pilot } = usePage().props;
    const { t } = useTranslation();

    if (!pilot) {
        return null;
    }

    return (
        <div
            role="note"
            className="flex items-center justify-center gap-2 bg-stamp-soft px-4 py-1.5 text-center text-xs font-medium text-stamp print:hidden"
        >
            <FlaskConical aria-hidden className="size-3.5 shrink-0" />
            <span>{t('pilot.banner')}</span>
        </div>
    );
}
