import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AdminLayout from '@/layouts/admin-layout';
import AppShell from '@/layouts/app-shell';
import AuthLayout from '@/layouts/auth-layout';
import OrgLayout from '@/layouts/org-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';
import WorkerLayout from '@/layouts/worker-layout';

const appName = import.meta.env.VITE_APP_NAME || 'BLUESKILLS';

void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    // The page folder decides the layout (CLAUDE.md: pages/{public,auth,worker,employer,agency,institute,admin}).
    layout: (name) => {
        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('auth/') || name.startsWith('onboarding/'):
                return AuthLayout;
            case name.startsWith('worker/'):
                return WorkerLayout;
            case /^(org|employer|agency|institute)\//.test(name):
                return OrgLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            case name.startsWith('settings/'):
                return [AppShell, SettingsLayout];
            default:
                return AppShell;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#153e9e',
    },
});

// This will set light / dark mode on load...
initializeTheme();
