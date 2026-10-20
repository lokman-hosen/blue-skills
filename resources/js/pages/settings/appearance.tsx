import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { useTranslation } from '@/lib/i18n';

export default function Appearance() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('settings.nav.appearance')} />
            <div className="flex flex-col gap-6">
                <Heading
                    variant="small"
                    title={t('settings.appearance.title')}
                    description={t('settings.appearance.body')}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}
