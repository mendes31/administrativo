/* Tiaraju PWA Service Worker — push e registro; páginas não são cacheadas aqui. */
var PUSH_ICON_CACHE = 'tiaraju-push-icons-v7';

var DEFAULT_ICON_PATH = 'public/adms/image/pwa-icon-192.png';
var DEFAULT_BADGE_PATHS = [
  'public/adms/image/pwa-badge-192.png',
  'public/adms/image/pwa-badge-96.png',
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

/**
 * Ícones sempre pelo escopo do PWA instalado — ignora URL_ADM do servidor (IP interno, outro host).
 */
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

function prefetchIntoCache(cache, url) {
  return fetch(url, { credentials: 'same-origin', cache: 'reload' })
    .then(function (res) {
      if (!res.ok) {
        return null;
      }
      return cache.put(url, res.clone()).then(function () {
        return url;
      });
    })
    .catch(function () {
      return null;
    });
}

self.addEventListener('install', function (event) {
  var scope = getScopeBase() + '/';
  var urls = [DEFAULT_ICON_PATH]
    .concat(DEFAULT_BADGE_PATHS)
    .map(function (path) {
      return new URL(path, scope).href;
    });

  event.waitUntil(
    caches.open(PUSH_ICON_CACHE).then(function (cache) {
      return Promise.all(
        urls.map(function (url) {
          return cache.add(url).catch(function () {
            return prefetchIntoCache(cache, url);
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

self.addEventListener('fetch', function () {
  // Sem interceptação de cache por enquanto.
});

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

function fetchFirstAvailableBlob(urls) {
  return urls.reduce(function (chain, url) {
    return chain.then(function (blob) {
      if (blob) {
        return blob;
      }
      return fetchAssetBlob(url);
    });
  }, Promise.resolve(null));
}

function buildNotificationOptions(payload, iconBlob, badgeBlob, iconUrl, badgeUrl) {
  var options = {
    body: payload.body || '',
    data: { url: payload.url || '/' },
    tag: 'tiaraju-push',
    renotify: true
  };

  if (iconBlob) {
    options.icon = iconBlob;
  } else if (iconUrl) {
    options.icon = iconUrl;
  }

  // Android (barra de status): badge = silhueta branca em blob; URL externa costuma virar sino.
  if (badgeBlob) {
    options.badge = badgeBlob;
  } else if (badgeUrl) {
    options.badge = badgeUrl;
  }

  return options;
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

  if (payload.maintenance === true) {
    event.waitUntil(Promise.resolve());
    return;
  }

  var iconUrl = resolvePushAssetUrl(payload.icon, DEFAULT_ICON_PATH);
  var badgeUrls = DEFAULT_BADGE_PATHS.map(function (path) {
    return resolvePushAssetUrl(payload.badge, path);
  });
  var badgeUrl = badgeUrls[0];
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
      fetchAssetBlob(iconUrl),
      fetchFirstAvailableBlob(badgeUrls)
    ])
      .then(function (results) {
        var iconBlob = results[0];
        var badgeBlob = results[1];
        var options = buildNotificationOptions(payload, iconBlob, badgeBlob, iconUrl, badgeUrl);
        return self.registration.showNotification(title, options);
      })
      .catch(function () {
        return self.registration.showNotification(title, buildNotificationOptions(payload, null, null, iconUrl, badgeUrl));
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
