import type { LucideIcon } from 'lucide-react';
import { Check, Clock, X } from 'lucide-react';
import type { Tone } from './tone';
import { isTone, toneClasses } from './tone';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const defaultIcons: Partial<Record<Tone, LucideIcon>> = { ok: Check, warn: Clock, bad: X };

type StatusChipProps = {
    /** A status from the server (StatusData). Its label is translated via `status.{value}` when a key exists. */
    status?: App.Support.Data.StatusData;
    label?: string;
    tone?: Tone;
    /** Pass `false` to hide the icon. Defaults to a check, clock or cross for ok, warn and bad. */
    icon?: LucideIcon | false;
    className?: string;
};

export function StatusChip({ status, label, tone, icon, className }: StatusChipProps) {
    const { t } = useTranslation();
    const resolvedTone: Tone = tone ?? (status && isTone(status.tone) ? status.tone : 'neutral');
    const text = label ?? (status ? t(`status.${status.value}`, {}, status.label) : '');
    const Icon = icon === false ? null : (icon ?? defaultIcons[resolvedTone] ?? null);

    return (
        <span
            className={cn(
                'inline-flex max-w-full items-center gap-1 rounded-full px-2 py-0.5 text-[11.5px] leading-5 font-semibold',
                toneClasses[resolvedTone],
                className,
            )}
        >
            {Icon && <Icon aria-hidden className="size-3 shrink-0" />}
            <span className="truncate">{text}</span>
        </span>
    );
}
