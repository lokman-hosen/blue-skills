import { Link } from '@inertiajs/react';
import type { Paginated } from '@/components/bs/jobs/types';
import { useTranslation } from '@/lib/i18n';

/** Previous / next links for a Laravel paginator, hidden when there is one page. */
export function Pager<T>({ page }: { page: Paginated<T> }) {
    const { t, formatNumber } = useTranslation();

    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav aria-label={t('pager.label')} className="flex items-center justify-between gap-2">
            {page.prev_page_url ? (
                <Link
                    href={page.prev_page_url}
                    preserveScroll
                    className="inline-flex min-h-11 items-center font-semibold text-brand"
                >
                    {t('jobs.search.previous')}
                </Link>
            ) : (
                <span />
            )}
            <span className="text-sm text-muted">
                {t('jobs.search.page', {
                    page: formatNumber(page.current_page),
                    last: formatNumber(page.last_page),
                })}
            </span>
            {page.next_page_url ? (
                <Link
                    href={page.next_page_url}
                    preserveScroll
                    className="inline-flex min-h-11 items-center font-semibold text-brand"
                >
                    {t('jobs.search.next')}
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}
