/* NexCart service worker: fast repeat visits + an offline page. Never touches admin, cart, forms or API calls. */
const VERSION = 'nexcart-v2';
const ASSETS = VERSION + '-assets';
const OFFLINE_HTML = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title>' +
    '<style>:root{color-scheme:dark light}body{font-family:system-ui,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;text-align:center;padding:24px;background:radial-gradient(60vmax 50vmax at 10% -10%,rgba(34,211,238,.22),transparent 60%),radial-gradient(50vmax 40vmax at 100% 100%,rgba(139,92,246,.25),transparent 60%),#070b16;color:#e6edf8}' +
    '.c{max-width:420px;padding:2rem;border-radius:28px;border:1px solid rgba(148,163,184,.25);background:rgba(15,23,42,.7);backdrop-filter:blur(14px)}.i{width:72px;height:72px;border-radius:22px;display:grid;place-items:center;margin:0 auto 1rem;font-size:2rem;background:linear-gradient(135deg,#22d3ee,#8b5cf6)}' +
    'h1{margin:.2rem 0}p{color:#9fb0c8}button{padding:.8rem 1.6rem;border-radius:14px;border:0;background:linear-gradient(120deg,#22d3ee,#10b981);font-weight:800;color:#041018;cursor:pointer}</style>' +
    '<div class="c"><div class="i">&#128268;</div><h1>You are offline</h1><p>Check your connection — your cart is safe. Medicines and prescriptions need the internet.</p><button onclick="location.reload()">Try again</button></div>';

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
