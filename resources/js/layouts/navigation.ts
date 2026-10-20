import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    Award,
    BookOpen,
    BriefcaseBusiness,
    Building2,
    ClipboardCheck,
    CreditCard,
    FileCheck,
    FileText,
    FolderKanban,
    GraduationCap,
    Home,
    IdCard,
    LayoutDashboard,
    ListChecks,
    MessageSquare,
    Palette,
    Plane,
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
};

export type NavGroup = { label?: string; items: NavLink[] };

// Worker: the first four are the mobile bottom tabs; the rest live in the "More" sheet.
export const workerPrimaryNav: NavLink[] = [
    { key: 'home', label: 'nav.home', icon: Home, href: dashboard() },
    { key: 'jobs', label: 'nav.jobs', icon: Search },
    { key: 'applications', label: 'nav.applications', icon: ListChecks },
    { key: 'passport', label: 'nav.passport', icon: IdCard, href: worker.passport.show() },
];

export const workerSecondaryNav: NavLink[] = [
    { key: 'skillshow', label: 'nav.skillshow', icon: Video, href: worker.skillshow.edit() },
    { key: 'messages', label: 'nav.messages', icon: MessageSquare },
    { key: 'documents', label: 'nav.documents', icon: FileText },
    { key: 'privacy', label: 'nav.privacy', icon: ShieldCheck, href: worker.privacy.show() },
    { key: 'settings', label: 'nav.settings', icon: Settings, href: profileSettings() },
];

const orgFooter: NavLink[] = [
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
                { key: 'talent', label: 'nav.talent', icon: Search },
                { key: 'shortlists', label: 'nav.shortlists', icon: Star },
                { key: 'projects', label: 'nav.projects', icon: FolderKanban },
                { key: 'messages', label: 'nav.messages', icon: MessageSquare },
            ],
        },
        {
            items: [
                { key: 'team', label: 'nav.team', icon: Users, href: team.index() },
                { key: 'billing', label: 'nav.billing', icon: CreditCard },
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
                { key: 'mandates', label: 'nav.mandates', icon: FileCheck },
                { key: 'job_orders', label: 'nav.job_orders', icon: BriefcaseBusiness },
                { key: 'candidates', label: 'nav.candidates', icon: UsersRound },
                { key: 'mobility', label: 'nav.mobility', icon: Plane },
                { key: 'compliance', label: 'nav.compliance', icon: ClipboardCheck },
                { key: 'messages', label: 'nav.messages', icon: MessageSquare },
            ],
        },
        {
            items: [
                { key: 'team', label: 'nav.team', icon: Users, href: team.index() },
                { key: 'billing', label: 'nav.billing', icon: CreditCard },
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
                { key: 'courses', label: 'nav.courses', icon: BookOpen },
                { key: 'cohorts', label: 'nav.cohorts', icon: GraduationCap },
                { key: 'graduates', label: 'nav.graduates', icon: UsersRound },
                { key: 'assessments', label: 'nav.assessments', icon: Award },
                { key: 'talent_pools', label: 'nav.talent_pools', icon: Star },
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
            { key: 'trust', label: 'nav.trust', icon: ShieldAlert },
            { key: 'crm', label: 'nav.crm', icon: Building2 },
        ],
    },
    {
        items: [
            { key: 'configuration', label: 'nav.configuration', icon: SlidersHorizontal },
            { key: 'taxonomy', label: 'nav.taxonomy', icon: Tags },
            {
                key: 'staff',
                label: 'nav.staff',
                icon: Users,
                href: admin.staff.index(),
                permission: 'staff.manage',
            },
            { key: 'design', label: 'nav.design_system', icon: Palette, href: design.index() },
            {
                key: 'queues',
                label: 'nav.queues',
                icon: Activity,
                href: horizon.index(),
                external: true,
            },
            { key: 'settings', label: 'nav.settings', icon: Settings, href: profileSettings() },
        ],
    },
];
