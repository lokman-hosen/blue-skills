import { Head } from '@inertiajs/react';
import { IdCard } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';

export default function WorkerHome() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.home')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.home')} />
                <EmptyState
                    icon={IdCard}
                    title={t('state.coming_soon.title')}
                    description={t('placeholder.worker_home')}
                />
            </div>
        </>
    );
}
