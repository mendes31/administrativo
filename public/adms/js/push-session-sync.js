(function () {
    'use strict';

    var cfg = window.__PushSessionSyncInit || {};
    var urlAdm = (cfg.urlAdm || '').replace(/\/$/, '');
    var csrfToken = cfg.csrfToken || '';
    var swVersion = cfg.swVersion || '20260520-10';
    var userId = cfg.userId || 0;

    function getServiceWorkerUrl() {
        return urlAdm + '/service-worker.js?v=' + encodeURIComponent(swVersion);
    }

    if (!urlAdm || !csrfToken || userId <= 0) {
        return;
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return;
    }

    var storageKey = 'adms_push_sync_v2_' + userId;
    if (sessionStorage.getItem(storageKey) === '1') {
        return;
    }

    function markChecked() {
        try {
            sessionStorage.setItem(storageKey, '1');
        } catch (e) {
            /* ignore */
        }
    }

    function fetchJson(url, options) {
        options = options || {};
        options.headers = options.headers || {};
        options.headers['Accept'] = 'application/json';
        options.headers['X-Requested-With'] = 'XMLHttpRequest';
        return fetch(url, options).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) {
                    throw new Error((data && data.message) || 'Erro na requisição');
                }
                return data;
            });
        });
    }

    function syncSubscription(registration, subscription) {
        var json = subscription.toJSON();
        var encodings = registration.pushManager.supportedContentEncodings || [];
        if (encodings.length > 0) {
            json.contentEncoding = encodings[0];
        }
        return fetchJson(urlAdm + '/push-subscribe/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: csrfToken,
                subscription: json
            })
        });
    }

    fetchJson(urlAdm + '/push-subscribe')
        .then(function (data) {
            if (!data.enabled || !data.configured) {
                markChecked();
                return;
            }
            return navigator.serviceWorker.register(getServiceWorkerUrl())
                .then(function () { return navigator.serviceWorker.ready; })
                .then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (subscription) {
                        if (!subscription) {
                            markChecked();
                            return;
                        }
                        var endpoint = subscription.endpoint || '';
                        if (endpoint === '') {
                            markChecked();
                            return;
                        }
                        return fetchJson(urlAdm + '/push-subscribe?endpoint=' + encodeURIComponent(endpoint))
                            .then(function (verify) {
                                if (verify.endpointRegistered) {
                                    markChecked();
                                    return;
                                }
                                return syncSubscription(registration, subscription).then(function () {
                                    markChecked();
                                });
                            });
                    });
                });
        })
        .catch(function () {
            /* Não marcar como verificado: permite nova tentativa após correção de layout/erro de rede. */
        });
})();
