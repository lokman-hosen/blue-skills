export type Tone = 'ok' | 'warn' | 'bad' | 'brand' | 'stamp' | 'neutral';

/** Soft background + strong text pairs from the locked design (prototype TONES). */
export const toneClasses: Record<Tone, string> = {
    ok: 'bg-ok-soft text-ok',
    warn: 'bg-warn-soft text-warn',
    bad: 'bg-bad-soft text-bad',
    brand: 'bg-brand-soft text-brand',
    stamp: 'bg-stamp-soft text-stamp',
    neutral: 'bg-surface-2 text-muted',
};

export function isTone(value: string): value is Tone {
    return value in toneClasses;
}
