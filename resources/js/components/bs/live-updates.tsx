import { router, usePage } from '@inertiajs/react';
import { useEcho, useEchoNotification } from '@laravel/echo-react';

const refreshBadges = () => router.reload({ only: ['unread'] });

/**
 * Keeps navigation badges current through Reverb (D-067). Broadcasts carry ids only; the page
 * reloads the counts the viewer is allowed to see.
 */
export function LiveUpdates() {
    const { auth, currentOrganization } = usePage().props;

    if (!auth.user) {
        return null;
    }

    return (
        <>
            <PersonalChannel userId={auth.user.id} />
            {currentOrganization && <InboxChannel organizationId={currentOrganization.id} />}
        </>
    );
}

function PersonalChannel({ userId }: { userId: string }) {
    useEcho(`users.${userId}`, '.message.sent', refreshBadges);
    useEchoNotification(`users.${userId}`, refreshBadges);

    return null;
}

function InboxChannel({ organizationId }: { organizationId: string }) {
    useEcho(`organizations.${organizationId}.inbox`, '.message.sent', refreshBadges);

    return null;
}
