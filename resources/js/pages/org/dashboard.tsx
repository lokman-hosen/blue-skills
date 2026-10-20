import { Head } from '@inertiajs/react';
import { LayoutDashboard } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';

export default function OrgDashboard() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.dashboard')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.dashboard')} />
                <EmptyState
                    icon={LayoutDashboard}
                    title={t('state.coming_soon.title')}
                    description={t('placeholder.org_dashboard')}
                />
            </div>
        </>
    );
}
