import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars -- must match React's generic signature
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            currentOrganization: App.Domain.Organization.Data.OrganizationSummaryData | null;
            organizations: App.Domain.Organization.Data.OrganizationSummaryData[];
            organizationAbilities: { update: boolean; manageTeam: boolean };
            locale: App.Domain.Config.Data.LocaleData;
            locales: App.Domain.Config.Data.LocaleOptionData[];
            translations: Record<string, string>;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
