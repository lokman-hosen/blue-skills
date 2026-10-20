import { Lock } from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusChip } from '@/components/bs/status-chip';
import { WorkspaceShell } from '@/components/bs/workspace-shell';
import { adminNav } from '@/layouts/navigation';
import { useTranslation } from '@/lib/i18n';

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();

    return (
        <WorkspaceShell
            nav={adminNav}
            context={
                <div className="flex min-w-0 flex-wrap items-center gap-2 px-2">
                    <span className="truncate text-sm font-semibold">{t('admin.workspace')}</span>
                    <StatusChip label={t('admin.staff_badge')} tone="brand" icon={Lock} />
                </div>
            }
        >
            {children}
        </WorkspaceShell>
    );
}
