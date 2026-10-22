/*
 * BLUESKILLS service worker (D-107). It only shows /offline.html when a page can't be reached.
 * Pages are never cached: they hold personal data (Skill Passport, applications, documents),
 * and every page must come fresh from the server. Bump VERSION when offline.html changes.
 */
const VERSION = 'v1';
const OFFLINE_CACHE = `blueskills-offline-${VERSION}`;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(OFFLINE_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL, '/favicon.svg']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== OFFLINE_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    // Full page loads only; Inertia visits, assets and uploads go straight to the network.
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL)));
});
