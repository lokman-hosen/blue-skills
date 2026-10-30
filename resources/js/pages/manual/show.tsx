import { Head, router } from '@inertiajs/react';
import { BookOpenText, Printer, Search } from 'lucide-react';
import { useEffect, useId, useMemo, useState } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import manualRoutes from '@/routes/manual';

type Manual = App.Domain.Manual.Data.ManualData;
type Role = App.Domain.Manual.Data.ManualRoleData;

const GROUPS = ['worker', 'employer', 'agency', 'institute', 'staff'];

/**
 * The User Manual (D-110): the manual of the signed-in user's role, with contents, search and
 * a clean printout. Superusers pick any role's manual; the server refuses everyone else.
 */
export default function ManualPage({
    manual,
    roles,
    canReadAll,
}: {
    manual: Manual | null;
    roles: Role[];
    canReadAll: boolean;
}) {
    const { t } = useTranslation();
    const [query, setQuery] = useState('');
    // Contents start open beside the manual on wide screens and folded above it on phones.
    // Rendered open on the server, then folded after hydration so the markup matches.
    const [contentsOpen, setContentsOpen] = useState(true);
    useEffect(() => {
        if (!window.matchMedia('(min-width: 64rem)').matches) {
            setContentsOpen(false);
        }
    }, []);
    const searchId = useId();
    const pickerId = useId();
    const needle = query.trim();

    const matching = useMemo(
        () =>
            new Set(
                (manual?.sections ?? [])
                    .filter(
                        (section) =>
                            needle === '' ||
                            contains(section.title, needle) ||
                            contains(plainText(section.html), needle),
                    )
                    .map((section) => section.id),
            ),
        [manual, needle],
    );

    if (manual === null) {
        return (
            <>
                <Head title={t('manual.title')} />
                <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                    <PageHeader title={t('manual.title')} />
                    <EmptyState
                        icon={BookOpenText}
                        title={t('manual.empty_title')}
                        description={t('manual.empty_body')}
                    />
                </div>
            </>
        );
    }

    const visible = manual.sections.filter((section) => matching.has(section.id));

    return (
        <>
            <Head title={`${manual.title} · ${t('manual.title')}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5">
                <PageHeader
                    eyebrow={t('manual.title')}
                    title={manual.title}
                    description={t(canReadAll ? 'manual.body_all' : 'manual.body')}
                    actions={
                        <Button
                            type="button"
                            variant="outline"
                            className="min-h-11 print:hidden"
                            onClick={() => window.print()}
                        >
                            <Printer aria-hidden className="size-4" />
                            {t('manual.print')}
                        </Button>
                    }
                />

                <div className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:flex-row sm:items-end print:hidden">
                    {roles.length > 1 && (
                        <div className="flex flex-col gap-1.5 sm:w-72">
                            <Label htmlFor={pickerId}>{t('manual.choose_role')}</Label>
                            <NativeSelect
                                id={pickerId}
                                value={manual.role}
                                onChange={(event) =>
                                    router.visit(manualRoutes.show.url(event.target.value))
                                }
                            >
                                {GROUPS.map((group) => {
                                    const inGroup = roles.filter((role) => role.group === group);

                                    return inGroup.length === 0 ? null : (
                                        <optgroup key={group} label={t(`manual.group.${group}`)}>
                                            {inGroup.map((role) => (
                                                <option key={role.value} value={role.value}>
                                                    {role.label}
                                                </option>
                                            ))}
                                        </optgroup>
                                    );
                                })}
                            </NativeSelect>
                        </div>
                    )}
                    <div className="flex flex-1 flex-col gap-1.5">
                        <Label htmlFor={searchId}>{t('manual.search')}</Label>
                        <div className="relative">
                            <Search
                                aria-hidden
                                className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted"
                            />
                            <Input
                                id={searchId}
                                type="search"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder={t('manual.search_placeholder')}
                                className="h-11 ps-9"
                            />
                        </div>
                    </div>
                </div>

                {manual.translationPending && (
                    <p className="rounded-xl bg-warn-soft px-4 py-3 text-sm text-ink print:hidden">
                        {t('manual.translation_pending')}
                    </p>
                )}

                {needle !== '' && (
                    <p aria-live="polite" className="text-sm text-muted print:hidden">
                        {visible.length === 0
                            ? t('manual.search_none', { query: needle })
                            : t('manual.search_count', { count: visible.length, query: needle })}
                    </p>
                )}

                <div className="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <nav
                        aria-label={t('manual.contents')}
                        className="lg:sticky lg:top-20 lg:self-start print:hidden"
                    >
                        <details
                            open={contentsOpen}
                            onToggle={(event) => setContentsOpen(event.currentTarget.open)}
                            className="rounded-2xl border border-line bg-surface p-4"
                        >
                            <summary className="flex min-h-11 cursor-pointer items-center font-semibold">
                                {t('manual.contents')}
                            </summary>
                            <ol className="mt-2 flex flex-col gap-0.5 text-sm">
                                {visible.map((section) => (
                                    <li key={section.id}>
                                        <a
                                            href={`#${section.id}`}
                                            className="flex min-h-11 items-center rounded-md px-2 text-muted hover:bg-surface-2 hover:text-ink lg:min-h-9"
                                        >
                                            {section.title}
                                        </a>
                                    </li>
                                ))}
                            </ol>
                        </details>
                    </nav>

                    <article className="flex min-w-0 flex-col gap-4">
                        {manual.introHtml !== '' && (
                            <div
                                className="manual-prose rounded-2xl border border-line bg-surface p-5 sm:p-6 print:border-0 print:p-0"
                                dangerouslySetInnerHTML={{
                                    __html: highlight(manual.introHtml, needle),
                                }}
                            />
                        )}
                        {visible.length === 0 && needle !== '' && (
                            <EmptyState
                                icon={Search}
                                className="print:hidden"
                                title={t('manual.search_none', { query: needle })}
                                description={t('manual.search_none_body')}
                            />
                        )}
                        {manual.sections.map((section) => (
                            <section
                                key={section.id}
                                id={section.id}
                                aria-labelledby={`${section.id}-title`}
                                className={`scroll-mt-20 rounded-2xl border border-line bg-surface p-5 sm:p-6 print:break-inside-auto print:border-0 print:p-0 ${matching.has(section.id) ? '' : 'hidden print:block'}`}
                            >
                                <h2
                                    id={`${section.id}-title`}
                                    className="font-display text-xl font-bold"
                                >
                                    {section.title}
                                </h2>
                                <div
                                    className="manual-prose mt-3"
                                    dangerouslySetInnerHTML={{
                                        __html: highlight(section.html, needle),
                                    }}
                                />
                            </section>
                        ))}
                    </article>
                </div>
            </div>
        </>
    );
}

const ENTITIES: Record<string, string> = {
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#39;': "'",
};

function plainText(html: string): string {
    return html.replace(/<[^>]+>/g, ' ').replace(/&(amp|lt|gt|quot|#39);/g, (m) => ENTITIES[m]);
}

function contains(text: string, needle: string): boolean {
    return text.toLocaleLowerCase().includes(needle.toLocaleLowerCase());
}

function escapeHtml(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/** Wraps matches in <mark>, touching only text outside tags and entities. */
function highlight(html: string, needle: string): string {
    if (needle === '') {
        return html;
    }

    const pattern = new RegExp(escapeHtml(needle).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');

    return html.replace(
        /(<[^>]+>|&[#a-z0-9]+;)|([^<&]+)/gi,
        (_, tag: string | undefined, text: string) =>
            tag ?? text.replace(pattern, (match) => `<mark>${match}</mark>`),
    );
}
