(function () {
    'use strict';

    var cfg = window.__PushNotificationsInit || {};
    var urlAdm = (cfg.urlAdm || '').replace(/\/$/, '');
    var csrfToken = cfg.csrfToken || '';

    var statusEl = document.getElementById('pushNotificationStatus');
    var btnEnable = document.getElementById('btnPushEnable');
    var btnDisable = document.getElementById('btnPushDisable');
    var alertEl = document.getElementById('pushNotificationAlert');

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

    function getServiceWorkerRegistration() {
        if (!('serviceWorker' in navigator)) {
            return Promise.reject(new Error('Service Worker não suportado neste navegador.'));
        }
        var swUrl = urlAdm + '/service-worker.js';
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
                    return;
                }

                return getServiceWorkerRegistration().then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (localSub) {
                        hideAlert();
                        if (localSub === null) {
                            setStatus('Desativadas neste dispositivo', 'bg-warning text-dark');
                            setButtons('idle');
                            if (data.subscribed || (data.subscriptionCount || 0) > 0) {
                                showAlert('info', 'Você já ativou push em outro dispositivo. Clique em "Ativar notificações" para receber também neste navegador.');
                            }
                            return;
                        }

                        return persistSubscription(registration, localSub).then(function () {
                            var endpoint = localSub.endpoint || '';
                            return fetchJson(urlAdm + '/push-subscribe?endpoint=' + encodeURIComponent(endpoint));
                        }).then(function (verify) {
                            if (verify.endpointRegistered) {
                                setStatus('Ativadas neste dispositivo', 'bg-success');
                                setButtons('subscribed');
                                return;
                            }
                            setStatus('Pendente sincronização', 'bg-warning text-dark');
                            setButtons('idle');
                            showAlert('warning', 'Push ativo neste navegador, mas ainda não confirmado no servidor. Clique em "Ativar notificações" para concluir.');
                        }).catch(function () {
                            setStatus('Pendente sincronização', 'bg-warning text-dark');
                            setButtons('idle');
                            showAlert('warning', 'Não foi possível sincronizar com o servidor. Clique em "Ativar notificações" novamente.');
                        });
                    });
                });
            })
            .catch(function (err) {
                setStatus('Erro ao consultar', 'bg-danger');
                setButtons('disabled');
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
                    return Notification.requestPermission().then(function (permission) {
                        if (permission !== 'granted') {
                            throw new Error('Permissão de notificação negada pelo navegador.');
                        }
                        return registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(data.publicKey)
                        }).then(function (subscription) {
                            return persistSubscription(registration, subscription);
                        });
                    });
                });
            })
            .then(function () {
                setStatus('Ativadas neste dispositivo', 'bg-success');
                setButtons('subscribed');
                showAlert('success', 'Notificações push ativadas com sucesso neste dispositivo.');
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
