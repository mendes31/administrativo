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

self.addEventListener('push', function (event) {
  var payload = { title: 'Portal Tiaraju', body: 'Nova notificação', url: '/', icon: '' };
  if (event.data) {
    try {
      var parsed = event.data.json();
      if (parsed && typeof parsed === 'object') {
        payload = Object.assign(payload, parsed);
      }
    } catch (e) {
      payload.body = event.data.text();
    }
  }

  var title = payload.title || 'Portal Tiaraju';
  var options = {
    body: payload.body || '',
    icon: payload.icon || undefined,
    badge: payload.icon || undefined,
    data: { url: payload.url || '/' },
    tag: 'tiaraju-push',
    renotify: true
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var targetUrl = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url && client.url.indexOf(targetUrl) !== -1 && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
      return undefined;
    })
  );
});
