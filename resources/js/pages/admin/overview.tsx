import { Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';

export default function AdminOverview() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.overview')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.overview')} />
                <EmptyState
                    icon={ShieldCheck}
                    title={t('state.coming_soon.title')}
                    description={t('placeholder.admin_overview')}
                />
            </div>
        </>
    );
}
