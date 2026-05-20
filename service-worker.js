/* Tiaraju PWA Service Worker v20260520-5 */
self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

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

function fetchAssetBlob(url) {
  if (!url) {
    return Promise.resolve(null);
  }
  return fetch(url, { credentials: 'same-origin', cache: 'no-store' })
    .then(function (res) {
      if (!res.ok) {
        return null;
      }
      return res.blob();
    })
    .catch(function () {
      return null;
    });
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
  var iconUrl = resolveNotificationAsset(payload.icon, base) || (base + '/public/adms/image/pwa-icon-192.png');
  var badgeUrl = resolveNotificationAsset(payload.badge, base) || (base + '/public/adms/image/pwa-badge-72.png');
  var title = payload.title || 'Portal Tiaraju';

  event.waitUntil(
    Promise.all([
      fetchAssetBlob(iconUrl),
      fetchAssetBlob(badgeUrl)
    ]).then(function (blobs) {
      var options = {
        body: payload.body || '',
        data: { url: payload.url || '/' },
        tag: 'tiaraju-push',
        renotify: true
      };

      if (blobs[0]) {
        options.icon = blobs[0];
      } else if (iconUrl) {
        options.icon = iconUrl;
      }

      // Badge inválido/ausente → Android mostra sino genérico. Só usar se carregou PNG monocromático.
      if (blobs[1]) {
        options.badge = blobs[1];
      }

      return self.registration.showNotification(title, options);
    })
  );
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
