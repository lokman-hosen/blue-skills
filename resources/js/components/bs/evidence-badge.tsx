import type { LucideIcon } from 'lucide-react';
import { CircleAlert, CircleDashed, Clock, FileUp, ShieldCheck, TimerOff } from 'lucide-react';
import { StatusChip } from './status-chip';
import type { Tone } from './tone';
import { useTranslation } from '@/lib/i18n';

/** Mirrors App\Domain\Verification\States\Evidence (spec §7). Keep the two in sync. */
export type EvidenceState =
    | 'self_declared'
    | 'uploaded_unreviewed'
    | 'verification_pending'
    | 'issuer_verified'
    | 'expired'
    | 'rejected';

const appearance: Record<EvidenceState, { tone: Tone; icon: LucideIcon }> = {
    self_declared: { tone: 'neutral', icon: CircleDashed },
    uploaded_unreviewed: { tone: 'neutral', icon: FileUp },
    verification_pending: { tone: 'warn', icon: Clock },
    issuer_verified: { tone: 'ok', icon: ShieldCheck },
    expired: { tone: 'bad', icon: TimerOff },
    rejected: { tone: 'bad', icon: CircleAlert },
};

/**
 * One evidence item's own state. Never aggregate these into a blanket "verified" claim
 * (product rule 9).
 */
export function EvidenceBadge({ state, className }: { state: EvidenceState; className?: string }) {
    const { t } = useTranslation();
    const { tone, icon } = appearance[state];

    return (
        <StatusChip label={t(`evidence.${state}`)} tone={tone} icon={icon} className={className} />
    );
}
