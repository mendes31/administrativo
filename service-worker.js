/* Tiaraju PWA Service Worker — push e registro; páginas não são cacheadas aqui. */
var PUSH_ICON_CACHE = 'tiaraju-push-icons-v8';

var DEFAULT_ICON_PATH = 'public/adms/image/pwa-icon-192.png';
var DEFAULT_BADGE_PATH = 'public/adms/image/pwa-badge-96.png';
var PUSH_ASSET_PATHS = [
  DEFAULT_ICON_PATH,
  'public/adms/image/pwa-badge-192.png',
  DEFAULT_BADGE_PATH,
  'public/adms/image/pwa-badge-72.png'
];

function getScopeBase() {
  if (self.registration && self.registration.scope) {
    return self.registration.scope.replace(/\/$/, '');
  }
  return self.location.origin;
}

function scopeAssetUrl(relativePath) {
  return new URL(String(relativePath).replace(/^\//, ''), getScopeBase() + '/').href;
}

function resolvePushAssetUrl(payloadValue, defaultRelativePath) {
  var fallback = scopeAssetUrl(defaultRelativePath);
  if (!payloadValue || typeof payloadValue !== 'string') {
    return fallback;
  }

  var trimmed = payloadValue.trim();
  if (trimmed === '') {
    return fallback;
  }

  try {
    if (trimmed.indexOf('http://') === 0 || trimmed.indexOf('https://') === 0) {
      var absolute = new URL(trimmed);
      var scopeOrigin = new URL(getScopeBase() + '/').origin;
      if (absolute.origin === scopeOrigin) {
        return absolute.href;
      }
      return fallback;
    }
    return scopeAssetUrl(trimmed);
  } catch (e) {
    return fallback;
  }
}

function ensureAssetCached(url) {
  return caches.open(PUSH_ICON_CACHE).then(function (cache) {
    return cache.match(url).then(function (cached) {
      if (cached) { return; }
      return fetch(url, { credentials: 'same-origin', cache: 'reload' })
        .then(function (res) {
          if (res.ok) { return cache.put(url, res.clone()); }
        })
        .catch(function () {});
    });
  }).catch(function () {});
}

self.addEventListener('install', function (event) {
  var scope = getScopeBase() + '/';
  var urls = PUSH_ASSET_PATHS.map(function (path) {
    return new URL(path, scope).href;
  });

  event.waitUntil(
    caches.open(PUSH_ICON_CACHE).then(function (cache) {
      return Promise.all(
        urls.map(function (url) {
          return cache.add(url).catch(function () {
            return fetch(url, { credentials: 'same-origin', cache: 'reload' })
              .then(function (res) {
                if (res.ok) { return cache.put(url, res.clone()); }
              })
              .catch(function () {});
          });
        })
      );
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys
          .filter(function (key) {
            return key.indexOf('tiaraju-push-icons-') === 0 && key !== PUSH_ICON_CACHE;
          })
          .map(function (key) {
            return caches.delete(key);
          })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('message', function (event) {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', function (event) {
  var url = event.request.url;
  if (url.indexOf('pwa-icon-') !== -1 || url.indexOf('pwa-badge-') !== -1) {
    event.respondWith(
      caches.open(PUSH_ICON_CACHE).then(function (cache) {
        return cache.match(event.request).then(function (cached) {
          if (cached) { return cached; }
          return fetch(event.request).then(function (res) {
            if (res.ok) { cache.put(event.request, res.clone()); }
            return res;
          });
        });
      }).catch(function () {
        return fetch(event.request);
      })
    );
  }
});

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

  if (payload.maintenance === true) {
    event.waitUntil(Promise.resolve());
    return;
  }

  var iconUrl = resolvePushAssetUrl(payload.icon, DEFAULT_ICON_PATH);
  var badgeUrl = resolvePushAssetUrl(payload.badge, DEFAULT_BADGE_PATH);
  var title = payload.title || 'Portal Tiaraju';

  if (payload.url && typeof payload.url === 'string' && payload.url.indexOf('http') !== 0) {
    try {
      payload.url = new URL(payload.url.replace(/^\//, ''), getScopeBase() + '/').href;
    } catch (e) {
      payload.url = getScopeBase() + '/dashboard';
    }
  }

  event.waitUntil(
    Promise.all([
      ensureAssetCached(iconUrl),
      ensureAssetCached(badgeUrl)
    ]).then(function () {
      return self.registration.showNotification(title, {
        body: payload.body || '',
        data: { url: payload.url || '/' },
        tag: 'tiaraju-push',
        renotify: true,
        icon: iconUrl,
        badge: badgeUrl
      });
    }).catch(function () {
      return self.registration.showNotification(title, {
        body: payload.body || '',
        data: { url: payload.url || '/' },
        tag: 'tiaraju-push',
        renotify: true,
        icon: iconUrl,
        badge: badgeUrl
      });
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

self.addEventListener('pushsubscriptionchange', function (event) {
  var base = getScopeBase();
  var oldEndpoint = event.oldSubscription ? event.oldSubscription.endpoint : '';
  event.waitUntil(
    self.registration.pushManager.subscribe(event.oldSubscription ? {
      userVisibleOnly: true,
      applicationServerKey: event.oldSubscription.options && event.oldSubscription.options.applicationServerKey
    } : { userVisibleOnly: true }).then(function (newSub) {
      var json = newSub.toJSON();
      return fetch(base + '/push-subscribe/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          subscription: json,
          sw_auto_renew: true,
          old_endpoint: oldEndpoint
        })
      });
    }).catch(function () {})
  );
});
