/* Tiaraju PWA Service Worker */
self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

// Estratégia pass-through para não alterar comportamento atual da aplicação
self.addEventListener('fetch', function () {
  // Sem interceptação de cache por enquanto.
});
