import { useCallback, useEffect, useRef, useState } from 'react';

export type RecorderState = 'idle' | 'requesting' | 'recording' | 'denied' | 'unsupported';

/** Formats in order of preference: Chrome/Firefox record WebM/Opus, Safari records MP4/AAC. */
const MIME_TYPES = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];

function supportedMimeType(): string | undefined {
    if (typeof MediaRecorder === 'undefined') {
        return undefined;
    }

    return MIME_TYPES.find((type) => MediaRecorder.isTypeSupported(type));
}

/**
 * Microphone capture for Voice-to-CV. Stops by itself after `maxSeconds`, and when the page is
 * hidden or the microphone goes away, so an interrupted answer is kept rather than lost.
 */
export function useVoiceRecorder({
    maxSeconds,
    onRecorded,
}: {
    maxSeconds: number;
    onRecorded: (file: File) => void;
}) {
    const supported =
        typeof window !== 'undefined' &&
        typeof MediaRecorder !== 'undefined' &&
        !!navigator.mediaDevices?.getUserMedia;
    const [state, setState] = useState<RecorderState>(supported ? 'idle' : 'unsupported');
    const [elapsed, setElapsed] = useState(0);
    const recorder = useRef<MediaRecorder | null>(null);
    const stream = useRef<MediaStream | null>(null);
    const chunks = useRef<Blob[]>([]);
    const timer = useRef<number | null>(null);
    const cancelled = useRef(false);
    const recordedCallback = useRef(onRecorded);

    useEffect(() => {
        recordedCallback.current = onRecorded;
    }, [onRecorded]);

    const release = useCallback(() => {
        if (timer.current !== null) {
            window.clearInterval(timer.current);
            timer.current = null;
        }

        stream.current?.getTracks().forEach((track) => track.stop());
        stream.current = null;
    }, []);

    const stop = useCallback(() => {
        if (recorder.current?.state === 'recording') {
            recorder.current.stop();
        }
    }, []);

    const cancel = useCallback(() => {
        cancelled.current = true;
        stop();
    }, [stop]);

    const start = useCallback(async () => {
        if (!supported) {
            setState('unsupported');

            return;
        }

        setState('requesting');

        try {
            stream.current = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch {
            setState('denied');

            return;
        }

        const mimeType = supportedMimeType();
        const instance = new MediaRecorder(
            stream.current,
            mimeType ? { mimeType, audioBitsPerSecond: 32_000 } : undefined,
        );

        chunks.current = [];
        cancelled.current = false;
        instance.ondataavailable = (event) => {
            if (event.data.size > 0) {
                chunks.current.push(event.data);
            }
        };
        instance.onstop = () => {
            release();
            setState('idle');

            if (cancelled.current || chunks.current.length === 0) {
                return;
            }

            const type = instance.mimeType || mimeType || 'audio/webm';
            const extension = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
            const blob = new Blob(chunks.current, { type: type.split(';')[0] });

            recordedCallback.current(
                new File([blob], `answer.${extension}`, { type: type.split(';')[0] }),
            );
        };
        stream.current.getTracks().forEach((track) => track.addEventListener('ended', stop));

        recorder.current = instance;
        instance.start(1000);
        setElapsed(0);
        setState('recording');

        const startedAt = Date.now();
        timer.current = window.setInterval(() => {
            const seconds = Math.floor((Date.now() - startedAt) / 1000);
            setElapsed(seconds);

            if (seconds >= maxSeconds) {
                stop();
            }
        }, 250);
    }, [maxSeconds, release, stop, supported]);

    useEffect(() => {
        const onHidden = () => {
            if (document.visibilityState === 'hidden') {
                stop();
            }
        };

        document.addEventListener('visibilitychange', onHidden);

        return () => {
            document.removeEventListener('visibilitychange', onHidden);
            cancelled.current = true;
            stop();
            release();
        };
    }, [release, stop]);

    return { state, elapsed, start, stop, cancel };
}
