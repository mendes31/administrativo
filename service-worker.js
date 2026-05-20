/* Tiaraju PWA Service Worker v20260520-2 */
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

function getAssetsBase(payload) {
  if (payload && payload.baseUrl) {
    return String(payload.baseUrl).replace(/\/$/, '');
  }
  if (self.registration && self.registration.scope) {
    return self.registration.scope.replace(/\/$/, '');
  }
  return self.location.origin;
}

function resolveNotificationAsset(path, base) {
  if (!path || typeof path !== 'string') {
    return undefined;
  }
  try {
    return new URL(path, base + '/').href;
  } catch (e) {
    return undefined;
  }
}

self.addEventListener('push', function (event) {
  var payload = { title: 'Portal Tiaraju', body: 'Nova notificação', url: '/', icon: '', badge: '' };
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

  var base = getAssetsBase(payload);
  var defaultIcon = base + '/public/adms/uploads/users/1/pwa-icon-512.png';
  var defaultBadge = base + '/public/adms/image/pwa-badge-96.png';
  var iconUrl = resolveNotificationAsset(payload.icon, base) || defaultIcon;
  // Badge Android: silhueta branca em fundo transparente (não reutilizar o ícone colorido).
  var badgeUrl = resolveNotificationAsset(payload.badge, base) || defaultBadge;

  var title = payload.title || 'Portal Tiaraju';
  var options = {
    body: payload.body || '',
    icon: iconUrl,
    badge: badgeUrl,
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
