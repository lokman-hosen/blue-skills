import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Messages from `lg`: the conversation list on the inline-start side, the page header and the
 * open thread beside it (D-114). The list keeps its place when moving between the list page and
 * a thread. Below `lg` the DOM order is the reading order (header, list or thread), so phones
 * keep list → thread navigation exactly as before.
 */
export function MessagesSplit({
    header,
    list,
    listLabel,
    showListOnMobile,
    children,
}: {
    header?: ReactNode;
    list: ReactNode;
    listLabel: string;
    /** The list page shows the list on phones; the thread page hides it there. */
    showListOnMobile: boolean;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-5 lg:grid-cols-[320px_minmax(0,1fr)] lg:items-start xl:grid-cols-[360px_minmax(0,1fr)]">
            {header && <div className="min-w-0 lg:col-start-2 lg:row-start-1">{header}</div>}
            <nav
                aria-label={listLabel}
                className={cn(
                    'min-w-0 lg:sticky lg:top-6 lg:col-start-1 lg:row-span-2 lg:row-start-1 lg:block lg:max-h-[calc(100svh-3rem)] lg:overflow-y-auto',
                    !showListOnMobile && 'hidden',
                )}
            >
                {list}
            </nav>
            {/* On the list page the second pane is desktop-only (phones open a thread instead). */}
            <div
                className={cn(
                    'min-w-0 flex-col gap-4 lg:col-start-2',
                    showListOnMobile ? 'hidden lg:flex' : 'flex',
                )}
            >
                {children}
            </div>
        </div>
    );
}
