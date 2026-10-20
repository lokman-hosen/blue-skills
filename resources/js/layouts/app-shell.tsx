import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AdminLayout from '@/layouts/admin-layout';
import OrgLayout from '@/layouts/org-layout';
import WorkerLayout from '@/layouts/worker-layout';

/**
 * Picks the signed-in user's workspace layout for shared pages such as settings.
 */
export default function AppShell({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    switch (auth.user?.type) {
        case 'worker':
            return <WorkerLayout>{children}</WorkerLayout>;
        case 'staff':
            return <AdminLayout>{children}</AdminLayout>;
        default:
            return <OrgLayout>{children}</OrgLayout>;
    }
}
