(function () {
    'use strict';

    var cfg = window.__PushNotificationsInit || {};
    var urlAdm = (cfg.urlAdm || '').replace(/\/$/, '');
    var csrfToken = cfg.csrfToken || '';
    var swVersion = cfg.swVersion || '20260520-9';

    var statusEl = document.getElementById('pushNotificationStatus');
    var btnEnable = document.getElementById('btnPushEnable');
    var btnDisable = document.getElementById('btnPushDisable');
    var alertEl = document.getElementById('pushNotificationAlert');
    var devicesSection = document.getElementById('pushDevicesSection');
    var devicesList = document.getElementById('pushDevicesList');

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function renderDevices(devices, currentEndpoint) {
        if (!devicesSection || !devicesList) {
            return;
        }
        if (!devices || devices.length === 0) {
            devicesSection.classList.add('d-none');
            devicesList.innerHTML = '';
            return;
        }
        devicesSection.classList.remove('d-none');
        devicesList.innerHTML = devices.map(function (device) {
            var isCurrent = !!(currentEndpoint && device.endpoint === currentEndpoint);
            return '<li class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 px-0">' +
                '<div>' +
                '<span class="fw-semibold">' + escapeHtml(device.label || 'Dispositivo') + '</span>' +
                (isCurrent ? ' <span class="badge bg-success ms-1">Este dispositivo</span>' : '') +
                '<div class="text-muted">Última sincronização: ' + escapeHtml(device.updated_at_fmt || '—') + '</div>' +
                '</div>' +
                '</li>';
        }).join('');
    }

    function currentBrowserKind() {
        var ua = navigator.userAgent || '';
        if (/Edg\//.test(ua)) {
            return 'edge';
        }
        if (/Chrome\//.test(ua)) {
            return 'chrome';
        }
        if (/Firefox\//.test(ua)) {
            return 'firefox';
        }
        return 'other';
    }

    function endpointChannel(endpoint) {
        if (!endpoint) {
            return 'none';
        }
        if (endpoint.indexOf('notify.windows.com') !== -1) {
            return 'wns';
        }
        if (endpoint.indexOf('fcm.googleapis.com') !== -1) {
            return 'fcm';
        }
        return 'other';
    }

    function browserMismatchHint(localSub, devices) {
        var browser = currentBrowserKind();
        var localEndpoint = localSub && localSub.endpoint ? localSub.endpoint : '';
        var hasWns = (devices || []).some(function (d) {
            return endpointChannel(d.endpoint) === 'wns';
        });
        var hasDesktopFcm = (devices || []).some(function (d) {
            return endpointChannel(d.endpoint) === 'fcm' && (d.label || '').indexOf('Windows') === 0;
        });

        if (localEndpoint === '' && browser === 'chrome' && hasWns && !hasDesktopFcm) {
            return 'Há push registrado no Microsoft Edge, mas este navegador é o Chrome. Clique em "Ativar notificações" aqui para registrar o Chrome no PC (Edge e Chrome são separados).';
        }
        if (localEndpoint === '' && browser === 'edge' && hasDesktopFcm && !hasWns) {
            return 'Há push registrado no Chrome, mas este navegador é o Edge. Clique em "Ativar notificações" aqui para registrar o Edge no PC.';
        }
        if (localEndpoint !== '' && browser === 'chrome' && endpointChannel(localEndpoint) === 'wns') {
            return 'A inscrição local é do Edge (Windows). Para receber no Chrome, desative aqui e ative novamente neste navegador.';
        }
        return '';
    }

    function refreshDevices(devices, localSub) {
        var currentEndpoint = localSub && localSub.endpoint ? localSub.endpoint : '';
        renderDevices(devices || [], currentEndpoint);
        var hint = browserMismatchHint(localSub, devices);
        if (hint) {
            showAlert('warning', hint);
        }
    }

    function isEndpointInDevices(endpoint, devices) {
        if (!endpoint || !devices || !devices.length) {
            return false;
        }
        return devices.some(function (device) {
            return device.endpoint === endpoint;
        });
    }

    function markSubscribed(localSub, devices) {
        refreshDevices(devices, localSub);
        setStatus('Ativadas neste dispositivo', 'bg-success');
        setButtons('subscribed');
        hideAlert();
    }

    function showAlert(type, message) {
        if (!alertEl) {
            return;
        }
        alertEl.className = 'alert alert-' + type + ' small mb-3';
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');
    }

    function hideAlert() {
        if (alertEl) {
            alertEl.classList.add('d-none');
        }
    }

    function setStatus(text, badgeClass) {
        if (!statusEl) {
            return;
        }
        statusEl.innerHTML = '<span class="badge ' + badgeClass + '">' + text + '</span>';
    }

    function setButtons(state) {
        if (btnEnable) {
            btnEnable.classList.toggle('d-none', state === 'subscribed' || state === 'unsupported' || state === 'disabled');
        }
        if (btnDisable) {
            btnDisable.classList.toggle('d-none', state !== 'subscribed');
        }
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
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

    function getServiceWorkerUrl() {
        return urlAdm + '/service-worker.js?v=' + encodeURIComponent(swVersion);
    }

    function permissionDeniedMessage() {
        if (currentBrowserKind() === 'edge') {
            return 'Notificações bloqueadas no Microsoft Edge. Abra edge://settings/content/notifications, coloque este site em "Permitir", ou clique no ícone de cadeado na barra de endereço → Permissões → Notificações.';
        }
        if (currentBrowserKind() === 'chrome') {
            return 'Notificações bloqueadas no Chrome. Clique no cadeado na barra de endereço → Notificações → Permitir.';
        }
        return 'Permissão de notificação negada. Libere nas configurações do navegador e tente novamente.';
    }

    function permissionDefaultMessage() {
        if (currentBrowserKind() === 'edge') {
            return 'O Edge não exibiu o pedido de permissão. Verifique edge://settings/content/notifications (modo silencioso) ou clique no cadeado na barra de endereço e permita notificações manualmente. Depois clique em "Ativar notificações" novamente.';
        }
        return 'Permissão de notificação não concedida. Clique em "Ativar notificações" e escolha Permitir no navegador.';
    }

    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            return Promise.reject(new Error('Notificações não suportadas neste navegador.'));
        }

        if (Notification.permission === 'granted') {
            return Promise.resolve('granted');
        }

        if (Notification.permission === 'denied') {
            return Promise.reject(new Error(permissionDeniedMessage()));
        }

        return Notification.requestPermission().then(function (result) {
            if (result === 'granted') {
                return result;
            }
            if (result === 'denied') {
                throw new Error(permissionDeniedMessage());
            }
            throw new Error(permissionDefaultMessage());
        });
    }

    function createPushSubscription(registration, publicKey) {
        var subscribeOptions = {
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey)
        };

        return registration.pushManager.getSubscription().then(function (existing) {
            if (!existing) {
                return registration.pushManager.subscribe(subscribeOptions);
            }

            return registration.pushManager.subscribe(subscribeOptions).catch(function () {
                return existing.unsubscribe().then(function () {
                    return registration.pushManager.subscribe(subscribeOptions);
                });
            });
        });
    }

    function getServiceWorkerRegistration() {
        if (!('serviceWorker' in navigator)) {
            return Promise.reject(new Error('Service Worker não suportado neste navegador.'));
        }
        var swUrl = getServiceWorkerUrl();
        return navigator.serviceWorker.register(swUrl).then(function () {
            return navigator.serviceWorker.ready;
        });
    }

    function buildSubscriptionPayload(registration, subscription) {
        var json = subscription.toJSON();
        var encodings = registration.pushManager.supportedContentEncodings || [];
        if (encodings.length > 0) {
            json.contentEncoding = encodings[0];
        }
        return json;
    }

    function persistSubscription(registration, subscription) {
        var json = buildSubscriptionPayload(registration, subscription);
        return fetchJson(urlAdm + '/push-subscribe/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: csrfToken,
                subscription: json
            })
        });
    }

    function loadStatus() {
        if (!('Notification' in window) || !('PushManager' in window)) {
            setStatus('Não suportado', 'bg-secondary');
            setButtons('unsupported');
            showAlert('secondary', 'Este navegador não suporta notificações push.');
            return;
        }

        fetchJson(urlAdm + '/push-subscribe')
            .then(function (data) {
                if (!data.enabled || !data.configured) {
                    setStatus('Indisponível no sistema', 'bg-secondary');
                    setButtons('disabled');
                    showAlert('warning', 'As notificações push ainda não estão ativas. Solicite ao administrador a configuração em Configuração Push (PWA).');
                    refreshDevices([], null);
                    return;
                }

                return getServiceWorkerRegistration().then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (localSub) {
                        refreshDevices(data.devices, localSub);
                        hideAlert();
                        if (localSub === null) {
                            setStatus('Desativadas neste dispositivo', 'bg-warning text-dark');
                            setButtons('idle');
                            if (Notification.permission === 'denied') {
                                showAlert('warning', permissionDeniedMessage());
                            } else if (currentBrowserKind() === 'edge' && Notification.permission === 'default') {
                                showAlert('info', 'No Microsoft Edge, clique em "Ativar notificações" para o navegador pedir permissão (Chrome e Edge registram push separadamente neste PC).');
                            } else if (data.subscribed || (data.subscriptionCount || 0) > 0) {
                                showAlert('info', 'Push já está ativo em outro aparelho ou navegador. Cada navegador (Chrome, Edge, celular) precisa ativar separadamente — clique em "Ativar notificações" aqui.');
                            }
                            return;
                        }

                        var endpoint = localSub.endpoint || '';

                        return fetchJson(urlAdm + '/push-subscribe?endpoint=' + encodeURIComponent(endpoint))
                            .then(function (verify) {
                                if (verify.endpointRegistered || isEndpointInDevices(endpoint, verify.devices || data.devices)) {
                                    markSubscribed(localSub, verify.devices || data.devices);
                                    return;
                                }

                                return persistSubscription(registration, localSub).then(function () {
                                    return fetchJson(urlAdm + '/push-subscribe?endpoint=' + encodeURIComponent(endpoint));
                                }).then(function (afterSave) {
                                    if (afterSave.endpointRegistered || isEndpointInDevices(endpoint, afterSave.devices || [])) {
                                        markSubscribed(localSub, afterSave.devices || data.devices);
                                        return;
                                    }
                                    setStatus('Pendente sincronização', 'bg-warning text-dark');
                                    setButtons('idle');
                                    showAlert('warning', 'Push ativo neste navegador, mas ainda não confirmado no servidor. Clique em "Ativar notificações" para concluir.');
                                });
                            })
                            .catch(function (err) {
                                if (isEndpointInDevices(endpoint, data.devices)) {
                                    markSubscribed(localSub, data.devices);
                                    return;
                                }
                                setStatus('Pendente sincronização', 'bg-warning text-dark');
                                setButtons('idle');
                                showAlert('warning', (err && err.message) ? err.message : 'Não foi possível sincronizar com o servidor. Clique em "Ativar notificações" novamente.');
                            });
                    });
                });
            })
            .catch(function (err) {
                setStatus('Erro ao consultar', 'bg-danger');
                setButtons('disabled');
                refreshDevices([], null);
                showAlert('danger', err.message || 'Não foi possível verificar o status das notificações.');
            });
    }

    function subscribePush() {
        hideAlert();
        if (btnEnable) {
            btnEnable.disabled = true;
        }

        fetchJson(urlAdm + '/push-subscribe')
            .then(function (data) {
                if (!data.publicKey) {
                    throw new Error('Chave pública VAPID não configurada.');
                }
                return getServiceWorkerRegistration().then(function (registration) {
                    return requestNotificationPermission().then(function () {
                        return createPushSubscription(registration, data.publicKey).then(function (subscription) {
                            return persistSubscription(registration, subscription);
                        });
                    });
                });
            })
            .then(function () {
                setStatus('Ativadas neste dispositivo', 'bg-success');
                setButtons('subscribed');
                showAlert('success', 'Notificações push ativadas com sucesso neste dispositivo.');
                loadStatus();
            })
            .catch(function (err) {
                showAlert('danger', err.message || 'Não foi possível ativar as notificações push.');
                loadStatus();
            })
            .finally(function () {
                if (btnEnable) {
                    btnEnable.disabled = false;
                }
            });
    }

    function unsubscribePush() {
        hideAlert();
        if (btnDisable) {
            btnDisable.disabled = true;
        }

        getServiceWorkerRegistration()
            .then(function (registration) {
                return registration.pushManager.getSubscription();
            })
            .then(function (subscription) {
                if (!subscription) {
                    return fetchJson(urlAdm + '/push-subscribe/unsubscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ csrf_token: csrfToken, endpoint: '' })
                    }).catch(function () {
                        return { success: true };
                    });
                }
                var endpoint = subscription.endpoint;
                return subscription.unsubscribe().then(function () {
                    return fetchJson(urlAdm + '/push-subscribe/unsubscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            csrf_token: csrfToken,
                            endpoint: endpoint
                        })
                    });
                });
            })
            .then(function () {
                setStatus('Desativadas neste dispositivo', 'bg-warning text-dark');
                setButtons('idle');
                showAlert('info', 'Notificações push desativadas neste dispositivo.');
                loadStatus();
            })
            .catch(function (err) {
                showAlert('danger', err.message || 'Não foi possível desativar as notificações push.');
                loadStatus();
            })
            .finally(function () {
                if (btnDisable) {
                    btnDisable.disabled = false;
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!statusEl) {
            return;
        }
        loadStatus();
        if (btnEnable) {
            btnEnable.addEventListener('click', subscribePush);
        }
        if (btnDisable) {
            btnDisable.addEventListener('click', unsubscribePush);
        }
    });
})();
