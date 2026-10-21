import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { cn } from '@/lib/utils';

/** A filter shown as a pill link with its count; the active one is filled. */
export function FilterPill({
    href,
    active,
    label,
    count,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    active: boolean;
    label: string;
    count?: string;
}) {
    return (
        <Link
            href={href}
            preserveScroll
            aria-current={active ? 'page' : undefined}
            className={cn(
                'inline-flex min-h-11 items-center gap-1.5 rounded-full border px-3 text-sm font-semibold',
                active
                    ? 'border-brand bg-brand text-on-brand'
                    : 'border-line bg-surface hover:border-brand/60',
            )}
        >
            {label}
            {count !== undefined && (
                <span className={cn('text-xs tnum', active ? 'opacity-90' : 'text-muted')}>
                    {count}
                </span>
            )}
        </Link>
    );
}
