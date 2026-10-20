import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type PageHeaderProps = {
    title: string;
    eyebrow?: string;
    description?: string;
    actions?: ReactNode;
    back?: { href: NonNullable<InertiaLinkProps['href']>; label: string };
    className?: string;
};

export function PageHeader({
    title,
    eyebrow,
    description,
    actions,
    back,
    className,
}: PageHeaderProps) {
    return (
        <header
            className={cn(
                'flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between',
                className,
            )}
        >
            <div className="flex min-w-0 flex-col gap-1">
                {back && (
                    <Link
                        href={back.href}
                        className="mb-1 inline-flex min-h-11 items-center gap-1.5 self-start text-sm font-semibold text-brand hover:underline sm:min-h-0"
                    >
                        <ArrowLeft aria-hidden className="size-4 rtl:rotate-180" />
                        {back.label}
                    </Link>
                )}
                {eyebrow && <p className="eyebrow text-brand">{eyebrow}</p>}
                <h1 className="font-display text-2xl leading-tight font-bold sm:text-3xl">
                    {title}
                </h1>
                {description && <p className="max-w-2xl text-sm text-muted">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </header>
    );
}
