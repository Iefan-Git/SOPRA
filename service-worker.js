// SOPRA service worker
//
// Deliberately conservative: only static assets (CSS, JS, icons, fonts)
// are cached for a faster/offline app-shell. Every .php page (payment
// ledger, duty assignments — including Confidential entries) is always
// fetched fresh from the network and NEVER cached, so a lost/shared
// device can't surface stale sensitive data from the cache after logout.
//
// Registered only on admin pages (see payment_ledger.php / duty_assignments.php),
// so only an authenticated Admin's browser ever installs this app —
// regular "user" accounts and anonymous visitors never load a page that
// references manifest.json or this file.

const CACHE_NAME = 'sopra-static-v1';
const STATIC_ASSETS = [
  'styles.css?v=3',
  'searchable_dropdown.js?v=1',
  'assets/logo.png',
  'assets/icon-192.png',
  'assets/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) =>
      Promise.all(names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Never intercept PHP pages/actions — always go straight to the
  // network so login, session state, and ledger/duty data are always
  // current and are never written into the cache.
  if (url.pathname.endsWith('.php') || url.pathname === '/' ) {
    return;
  }

  // Static assets: cache-first, falling back to network.
  event.respondWith(
    caches.match(event.request).then((cached) => {
      return cached || fetch(event.request).then((resp) => {
        const copy = resp.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy)).catch(() => {});
        return resp;
      }).catch(() => cached);
    })
  );
});
