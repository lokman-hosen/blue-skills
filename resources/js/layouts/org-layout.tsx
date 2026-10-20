import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { OrgSwitcher } from '@/components/bs/org-switcher';
import { WorkspaceNotices } from '@/components/bs/workspace-notices';
import { WorkspaceShell } from '@/components/bs/workspace-shell';
import { orgNav } from '@/layouts/navigation';

/** Employer, agency and institute workspaces: navigation follows the current organization's type. */
export default function OrgLayout({ children }: { children: ReactNode }) {
    const { currentOrganization } = usePage().props;

    return (
        <WorkspaceShell
            nav={orgNav[currentOrganization?.type ?? 'employer']}
            context={<OrgSwitcher />}
        >
            <WorkspaceNotices />
            {children}
        </WorkspaceShell>
    );
}
