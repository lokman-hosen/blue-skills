import { cn } from '@/lib/utils';

type KpiTileProps = {
    label: string;
    value: string | number;
    sub?: string;
    className?: string;
};

export function KpiTile({ label, value, sub, className }: KpiTileProps) {
    return (
        <div className={cn('min-w-0 rounded-xl border border-line bg-surface p-4', className)}>
            <p className="truncate text-xs text-muted">{label}</p>
            <p className="mt-1 truncate font-display text-3xl font-bold tnum">{value}</p>
            {sub && <p className="mt-0.5 truncate text-[11.5px] text-muted">{sub}</p>}
        </div>
    );
}
