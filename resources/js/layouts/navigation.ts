import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    BarChart3,
    BookOpen,
    BookOpenText,
    BriefcaseBusiness,
    ClipboardCheck,
    Contact,
    CreditCard,
    FileCheck,
    FileText,
    Flag,
    FolderKanban,
    GraduationCap,
    Home,
    IdCard,
    KeyRound,
    LayoutDashboard,
    ListChecks,
    Megaphone,
    MessageSquare,
    Palette,
    Plane,
    ScrollText,
    Search,
    Settings,
    ShieldAlert,
    ShieldCheck,
    SlidersHorizontal,
    Star,
    Tags,
    Users,
    UsersRound,
    Video,
} from 'lucide-react';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import design from '@/routes/design';
import horizon from '@/routes/horizon';
import jobs from '@/routes/jobs';
import manual from '@/routes/manual';
import org from '@/routes/org';
import { edit as profileSettings } from '@/routes/profile';
import team from '@/routes/team';
import worker from '@/routes/worker';

export type NavLink = {
    key: string;
    /** Translation key. */
    label: string;
    icon: LucideIcon;
    /** Missing until the module that owns the screen ships; rendered as "Soon". */
    href?: NonNullable<InertiaLinkProps['href']>;
    external?: boolean;
    /** Staff permission required to see the item. */
    permission?: string;
    /** Organization ability (shared `organizationAbilities`) required to see the item. */
    ability?:
        | 'reviewCompliance'
        | 'viewCases'
        | 'viewApplicants'
        | 'viewProjects'
        | 'viewAnalytics'
        | 'viewBilling'
        | 'viewMandates'
        | 'viewCourses'
        | 'managePools'
        | 'viewPlacements'
        | 'browsePools'
        | 'searchTalent'
        | 'viewShortlists';
};

/** Unread count shown beside a navigation item, from the shared `unread` prop. */
export function navBadge(
    key: string,
    unread: { messages: number; notifications: number } | null,
): number {
    return key === 'messages' ? (unread?.messages ?? 0) : 0;
}

export type NavGroup = { label?: string; items: NavLink[] };

// Worker: the first four are the mobile bottom tabs; the rest live in the "More" sheet.
export const workerPrimaryNav: NavLink[] = [
    { key: 'home', label: 'nav.home', icon: Home, href: dashboard() },
    { key: 'jobs', label: 'nav.jobs', icon: Search, href: jobs.index() },
    {
        key: 'applications',
        label: 'nav.applications',
        icon: ListChecks,
        href: worker.applications.index(),
    },
    { key: 'passport', label: 'nav.passport', icon: IdCard, href: worker.passport.show() },
];

export const workerSecondaryNav: NavLink[] = [
    { key: 'skillshow', label: 'nav.skillshow', icon: Video, href: worker.skillshow.edit() },
    { key: 'messages', label: 'nav.messages', icon: MessageSquare, href: worker.messages.index() },
    { key: 'documents', label: 'nav.documents', icon: FileText, href: worker.documents.index() },
    { key: 'privacy', label: 'nav.privacy', icon: ShieldCheck, href: worker.privacy.show() },
    { key: 'manual', label: 'nav.manual', icon: BookOpenText, href: manual.index() },
    { key: 'settings', label: 'nav.settings', icon: Settings, href: profileSettings() },
];

const orgFooter: NavLink[] = [
    { key: 'manual', label: 'nav.manual', icon: BookOpenText, href: manual.index() },
    { key: 'settings', label: 'nav.settings', icon: Settings, href: profileSettings() },
];

export const orgNav: Record<App.Domain.Organization.Enums.OrganizationType, NavGroup[]> = {
    employer: [
        {
            items: [
                {
                    key: 'dashboard',
                    label: 'nav.dashboard',
                    icon: LayoutDashboard,
                    href: dashboard(),
                },
                { key: 'jobs', label: 'nav.jobs', icon: BriefcaseBusiness, href: org.jobs.index() },
                {
                    key: 'talent',
                    label: 'nav.talent',
                    icon: Search,
                    href: org.talent.index(),
                    ability: 'searchTalent',
                },
                {
                    key: 'shortlists',
                    label: 'nav.shortlists',
                    icon: Star,
                    href: org.shortlists.index(),
                    ability: 'viewShortlists',
                },
                {
                    key: 'institute_pools',
                    label: 'nav.institute_pools',
                    icon: GraduationCap,
                    href: org.institutePools.index(),
                    ability: 'browsePools',
                },
                {
                    key: 'mobility',
                    label: 'nav.mobility',
                    icon: Plane,
                    href: org.cases.index(),
                    ability: 'viewCases',
                },
                {
                    key: 'projects',
                    label: 'nav.projects',
                    icon: FolderKanban,
                    href: org.projects.index(),
                    ability: 'viewProjects',
                },
                {
                    key: 'analytics',
                    label: 'nav.analytics',
                    icon: BarChart3,
                    href: org.analytics.index(),
                    ability: 'viewAnalytics',
                },
                {
                    key: 'messages',
                    label: 'nav.messages',
                    icon: MessageSquare,
                    href: org.messages.index(),
                },
            ],
        },
        {
            items: [
                { key: 'team', label: 'nav.team', icon: Users, href: team.index() },
                {
                    key: 'billing',
                    label: 'nav.billing',
                    icon: CreditCard,
                    href: org.billing.index(),
                    ability: 'viewBilling',
                },
                ...orgFooter,
            ],
        },
    ],
    agency: [
        {
            items: [
                {
                    key: 'dashboard',
                    label: 'nav.dashboard',
                    icon: LayoutDashboard,
                    href: dashboard(),
                },
                {
                    key: 'mandates',
                    label: 'nav.mandates',
                    icon: FileCheck,
                    href: org.mandates.index(),
                    ability: 'viewMandates',
                },
                {
                    key: 'job_orders',
                    label: 'nav.job_orders',
                    icon: BriefcaseBusiness,
                    href: org.jobs.index(),
                },
                {
                    key: 'candidates',
                    label: 'nav.candidates',
                    icon: UsersRound,
                    href: org.candidates.index(),
                    ability: 'viewApplicants',
                },
                {
                    key: 'institute_pools',
                    label: 'nav.institute_pools',
                    icon: GraduationCap,
                    href: org.institutePools.index(),
                    ability: 'browsePools',
                },
                {
                    key: 'mobility',
                    label: 'nav.mobility',
                    icon: Plane,
                    href: org.cases.index(),
                    ability: 'viewCases',
                },
                {
                    key: 'analytics',
                    label: 'nav.analytics',
                    icon: BarChart3,
                    href: org.analytics.index(),
                    ability: 'viewAnalytics',
                },
                {
                    key: 'compliance',
                    label: 'nav.compliance',
                    icon: ClipboardCheck,
                    href: org.compliance(),
                    ability: 'reviewCompliance',
                },
                {
                    key: 'messages',
                    label: 'nav.messages',
                    icon: MessageSquare,
                    href: org.messages.index(),
                },
            ],
        },
        {
            items: [
                {
                    key: 'licence',
                    label: 'nav.licence',
                    icon: IdCard,
                    href: org.licence.edit(),
                    ability: 'viewMandates',
                },
                { key: 'team', label: 'nav.team', icon: Users, href: team.index() },
                {
                    key: 'billing',
                    label: 'nav.billing',
                    icon: CreditCard,
                    href: org.billing.index(),
                    ability: 'viewBilling',
                },
                ...orgFooter,
            ],
        },
    ],
    institute: [
        {
            items: [
                {
                    key: 'dashboard',
                    label: 'nav.dashboard',
                    icon: LayoutDashboard,
                    href: dashboard(),
                },
                {
                    key: 'courses',
                    label: 'nav.courses',
                    icon: BookOpen,
                    href: org.courses.index(),
                    ability: 'viewCourses',
                },
                {
                    key: 'cohorts',
                    label: 'nav.cohorts',
                    icon: GraduationCap,
                    href: org.cohorts.index(),
                    ability: 'viewCourses',
                },
                {
                    key: 'graduates',
                    label: 'nav.graduates',
                    icon: UsersRound,
                    href: org.graduates.index(),
                    ability: 'viewCourses',
                },
                {
                    key: 'talent_pools',
                    label: 'nav.talent_pools',
                    icon: Star,
                    href: org.pools.index(),
                    ability: 'managePools',
                },
                {
                    key: 'placements',
                    label: 'nav.placements',
                    icon: Plane,
                    href: org.placements.index(),
                    ability: 'viewPlacements',
                },
            ],
        },
        {
            items: [
                { key: 'team', label: 'nav.team', icon: Users, href: team.index() },
                ...orgFooter,
            ],
        },
    ],
};

export const adminNav: NavGroup[] = [
    {
        items: [
            { key: 'overview', label: 'nav.overview', icon: LayoutDashboard, href: dashboard() },
            {
                key: 'verifications',
                label: 'nav.verifications',
                icon: ShieldCheck,
                href: admin.organizations.index(),
                permission: 'verifications.review',
            },
            {
                key: 'evidence',
                label: 'nav.evidence_reviews',
                icon: FileCheck,
                href: admin.evidence.index(),
                permission: 'verifications.review',
            },
            {
                key: 'skillshows',
                label: 'nav.skillshows',
                icon: Video,
                href: admin.skillshows.index(),
                permission: 'skill_shows.moderate',
            },
            {
                key: 'jobs',
                label: 'nav.job_reviews',
                icon: BriefcaseBusiness,
                href: admin.jobs.index(),
                permission: 'jobs.moderate',
            },
            {
                key: 'mandates',
                label: 'nav.mandate_reviews',
                icon: FileCheck,
                href: admin.mandates.index(),
                permission: 'verifications.review',
            },
            {
                key: 'trust_reports',
                label: 'nav.trust_reports',
                icon: Flag,
                href: admin.trust.reports.index(),
                permission: 'trust.manage',
            },
            {
                key: 'trust',
                label: 'nav.trust',
                icon: ShieldAlert,
                href: admin.trust.cases.index(),
                permission: 'trust.manage',
            },
            {
                key: 'crm',
                label: 'nav.crm',
                icon: Contact,
                href: admin.crm.index(),
                permission: 'crm.manage',
            },
            {
                key: 'marketing',
                label: 'nav.marketing',
                icon: Megaphone,
                href: admin.marketing.index(),
                permission: 'crm.manage',
            },
            {
                key: 'admin_billing',
                label: 'nav.billing',
                icon: CreditCard,
                href: admin.billing.index(),
                permission: 'finance.manage',
            },
        ],
    },
    {
        items: [
            {
                key: 'configuration',
                label: 'nav.configuration',
                icon: SlidersHorizontal,
                href: admin.config.index(),
                permission: 'config.manage',
            },
            {
                key: 'taxonomy',
                label: 'nav.taxonomy',
                icon: Tags,
                href: admin.taxonomy.index(),
                permission: 'taxonomy.manage',
            },
            {
                key: 'audit',
                label: 'nav.audit',
                icon: ScrollText,
                href: admin.audit.index(),
                permission: 'staff.manage',
            },
            {
                key: 'staff',
                label: 'nav.staff',
                icon: Users,
                href: admin.staff.index(),
                permission: 'staff.manage',
            },
            {
                key: 'roles',
                label: 'nav.roles',
                icon: KeyRound,
                href: admin.roles.index(),
                permission: 'staff.manage',
            },
            { key: 'design', label: 'nav.design_system', icon: Palette, href: design.index() },
            {
                key: 'queues',
                label: 'nav.queues',
                icon: Activity,
                href: horizon.index(),
                external: true,
                permission: 'horizon.view',
            },
            { key: 'manual', label: 'nav.manual', icon: BookOpenText, href: manual.index() },
            { key: 'settings', label: 'nav.settings', icon: Settings, href: profileSettings() },
        ],
    },
];
