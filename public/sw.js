/* NexCart service worker: fast repeat visits + an offline page. Never touches admin, cart, forms or API calls. */
const VERSION = 'nexcart-v1';
const ASSETS = VERSION + '-assets';
const OFFLINE_HTML = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title>' +
    '<body style="font-family:system-ui;background:#070b16;color:#e6edf8;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center">' +
    '<div><h1>You are offline</h1><p>Check your connection and try again.</p><button onclick="location.reload()" style="padding:.7rem 1.4rem;border-radius:12px;border:0;background:#22d3ee;font-weight:700">Retry</button></div>';

self.addEventListener('install', (e) => self.skipWaiting());
self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
    const req = e.request, url = new URL(req.url);
    if (req.method !== 'GET' || url.origin !== location.origin) return;
    if (/^\/(admin|login|logout|setup|cart|wishlist|lang|search)/.test(url.pathname)) return;

    // static files: stale-while-revalidate
    if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/storage/')) {
        e.respondWith(caches.open(ASSETS).then(async (cache) => {
            const hit = await cache.match(req);
            const fresh = fetch(req).then((res) => { if (res.ok) cache.put(req, res.clone()); return res; }).catch(() => hit);
            return hit || fresh;
        }));
        return;
    }

    // pages: network first, offline page on failure
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => new Response(OFFLINE_HTML, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
    }
});
