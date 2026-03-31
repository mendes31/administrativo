const CACHE_NAME = 'tiaraju-pwa-v1';
const URL_PREFIX = '/administrativo/';

// Rotas e assets principais para cache inicial
const PRECACHE_URLS = [
  URL_PREFIX,
  URL_PREFIX + 'dashboard',
  URL_PREFIX + 'timeline',
  URL_PREFIX + 'public/adms/css/bootstrap.min.css',
  URL_PREFIX + 'public/adms/css/styles_admin.css',
  URL_PREFIX + 'public/adms/css/custom_adms.css',
  URL_PREFIX + 'public/adms/js/bootstrap.bundle.min.js',
  URL_PREFIX + 'public/adms/js/sbadmin.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_URLS);
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
          return null;
        })
      )
    )
  );
});

// Estratégia simples: network first com fallback para cache
self.addEventListener('fetch', (event) => {
  const request = event.request;

  // Só intercepta GET dentro do escopo /administrativo/
  if (request.method !== 'GET' || !request.url.includes(URL_PREFIX)) {
    return;
  }

  event.respondWith(
    fetch(request)
      .then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        return response;
      })
      .catch(() =>
        caches.match(request).then((cached) => cached || Promise.reject('no-match'))
      )
  );
});

