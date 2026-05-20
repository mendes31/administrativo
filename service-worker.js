/* Tiaraju PWA Service Worker v20260520-7 */
var PUSH_ICON_CACHE = 'tiaraju-push-icons-v2';

self.addEventListener('install', function (event) {
  var scope = self.registration && self.registration.scope
    ? self.registration.scope
    : (self.location.origin + '/');
  var assets = [
    'public/adms/image/pwa-icon-192.png',
    'public/adms/image/pwa-badge-72.png',
    'public/adms/image/pwa-badge-96.png'
  ].map(function (path) {
    return new URL(path, scope).href;
  });

  event.waitUntil(
    caches.open(PUSH_ICON_CACHE).then(function (cache) {
      return Promise.all(
        assets.map(function (url) {
          return cache.add(url).catch(function () {
            return null;
          });
        })
      );
    }).then(function () {
      return self.skipWaiting();
    })
  );
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

  return caches.open(PUSH_ICON_CACHE).then(function (cache) {
    return cache.match(url).then(function (cached) {
      if (cached) {
        return cached.blob();
      }
      return fetch(url, { credentials: 'same-origin', cache: 'reload' }).then(function (res) {
        if (!res.ok) {
          return null;
        }
        var copy = res.clone();
        cache.put(url, copy).catch(function () {});
        return res.blob();
      });
    });
  }).catch(function () {
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
