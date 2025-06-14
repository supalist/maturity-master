const CACHE_NAME = 'maturity-cache-v1';
const URLS_TO_CACHE = [
  '/',
  '/mm.css',
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/create_event.php',
  '/view_event.php'
];

// Install & cache core assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(URLS_TO_CACHE))
  );
});

self.addEventListener('fetch', event => {
  if (event.request.url.includes('userhome.php')) {
    // Always fetch fresh version from the network
    event.respondWith(fetch(event.request));
    return;
  }

  // Everything else: serve from cache or fallback to network
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
