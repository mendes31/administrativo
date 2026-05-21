(function () {
    'use strict';

    var cfg = window.__PwaAppInit || {};
    var urlAdm = (cfg.urlAdm || '').replace(/\/$/, '');
    var swVersion = cfg.swVersion || '20260520-13';
    var csrfToken = cfg.csrfToken || '';
    var STORAGE_HINT = 'adms_pwa_browser_hint_dismissed';
    var STORAGE_UPDATE_LATER = 'adms_pwa_update_later';
    var STORAGE_PUSH_LATER = 'adms_pwa_push_banner_later';

    var deferredInstallPrompt = null;
    var swRegistration = null;
    var pushNeedsActivation = false;
    var pushCheckDone = false;

    function syncDeferredInstallPrompt() {
        if (window.__admsDeferredInstallPrompt && !deferredInstallPrompt) {
            deferredInstallPrompt = window.__admsDeferredInstallPrompt;
        }
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredInstallPrompt = e;
        window.__admsDeferredInstallPrompt = e;
        window.dispatchEvent(new Event('adms-pwa-installable'));
        refreshInstallUi();
    });

    window.addEventListener('appinstalled', function () {
        deferredInstallPrompt = null;
        window.__admsDeferredInstallPrompt = null;
        refreshInstallUi();
    });

    function ua() {
        return navigator.userAgent || '';
    }

    function isIOS() {
        return /iPad|iPhone|iPod/i.test(ua()) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function isIOSSafari() {
        if (!isIOS()) {
            return false;
        }
        var u = ua();
        return /Safari/i.test(u) && !/CriOS|FxiOS|EdgiOS|OPiOS/i.test(u);
    }

    function isFirefox() {
        return /Firefox\//i.test(ua());
    }

    function isEdge() {
        return /Edg\//i.test(ua());
    }

    function isChromeAndroid() {
        var u = ua();
        return /Android/i.test(u) &&
            /Chrome/i.test(u) &&
            !/Edg/i.test(u) &&
            !/Firefox/i.test(u) &&
            !/OPR\//i.test(u);
    }

    function isPwaInstalled() {
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        if (window.navigator.standalone === true) {
            return true;
        }
        return false;
    }

    function canUseNativeInstallPrompt() {
        syncDeferredInstallPrompt();
        return !!deferredInstallPrompt;
    }

    function waitForInstallPrompt(maxMs) {
        return new Promise(function (resolve) {
            syncDeferredInstallPrompt();
            if (canUseNativeInstallPrompt()) {
                resolve(true);
                return;
            }
            var settled = false;
            var finish = function () {
                if (settled) {
                    return;
                }
                settled = true;
                document.removeEventListener('adms-pwa-installable', onReady);
                clearTimeout(timer);
                syncDeferredInstallPrompt();
                resolve(canUseNativeInstallPrompt());
            };
            var onReady = function () {
                finish();
            };
            var timer = setTimeout(finish, maxMs);
            document.addEventListener('adms-pwa-installable', onReady);
        });
    }

    function runNativeInstallPrompt() {
        if (!canUseNativeInstallPrompt()) {
            return Promise.resolve(false);
        }
        var promptEvent = deferredInstallPrompt;
        promptEvent.prompt();
        return promptEvent.userChoice.then(function (choice) {
            deferredInstallPrompt = null;
            window.__admsDeferredInstallPrompt = null;
            if (choice.outcome === 'accepted') {
                setInstallStatus('Instalado', 'bg-success');
                hideInstallButtons();
            }
            refreshInstallUi();
            return choice.outcome === 'accepted';
        });
    }

    function getServiceWorkerUrl() {
        return urlAdm + '/service-worker.js?v=' + encodeURIComponent(swVersion);
    }

    function isUpdateDeferred() {
        try {
            var laterTs = parseInt(sessionStorage.getItem(STORAGE_UPDATE_LATER) || '0', 10);
            return laterTs > 0 && (Date.now() - laterTs) < 60 * 60 * 1000;
        } catch (e) {
            return false;
        }
    }

    function isPushBannerDeferred() {
        try {
            var laterTs = parseInt(sessionStorage.getItem(STORAGE_PUSH_LATER) || '0', 10);
            return laterTs > 0 && (Date.now() - laterTs) < 24 * 60 * 60 * 1000;
        } catch (e) {
            return false;
        }
    }

    function isUpdatePending() {
        return !!(
            swRegistration &&
            swRegistration.waiting &&
            navigator.serviceWorker &&
            navigator.serviceWorker.controller
        );
    }

    function deferUpdate() {
        try {
            sessionStorage.setItem(STORAGE_UPDATE_LATER, String(Date.now()));
        } catch (e) { /* ignore */ }
        refreshPwaPromoState();
    }

    function deferPushBanner() {
        try {
            sessionStorage.setItem(STORAGE_PUSH_LATER, String(Date.now()));
        } catch (e) { /* ignore */ }
        refreshPwaPromoState();
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

    function getSwRegistration() {
        if (!('serviceWorker' in navigator)) {
            return Promise.reject(new Error('Service Worker indisponível'));
        }
        return navigator.serviceWorker.register(getServiceWorkerUrl()).then(function () {
            return navigator.serviceWorker.ready;
        });
    }

    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            return Promise.reject(new Error('Notificações não suportadas neste navegador.'));
        }
        if (Notification.permission === 'granted') {
            return Promise.resolve('granted');
        }
        if (Notification.permission === 'denied') {
            return Promise.reject(new Error('Permissão de notificação negada. Libere nas configurações do navegador ou em Meu Perfil.'));
        }
        return Notification.requestPermission().then(function (result) {
            if (result === 'granted') {
                return result;
            }
            throw new Error('Permissão não concedida. Toque em Permitir quando o navegador solicitar.');
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

    function persistPushSubscription(registration, subscription) {
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

    function checkPushNeedsActivation() {
        pushCheckDone = false;
        pushNeedsActivation = false;

        if (!urlAdm || !csrfToken) {
            pushCheckDone = true;
            return Promise.resolve();
        }
        if (!('Notification' in window) || !('PushManager' in window)) {
            pushCheckDone = true;
            return Promise.resolve();
        }
        if (Notification.permission === 'denied') {
            pushCheckDone = true;
            return Promise.resolve();
        }
        if (!isPwaInstalled()) {
            pushCheckDone = true;
            return Promise.resolve();
        }

        return fetchJson(urlAdm + '/push-subscribe')
            .then(function (data) {
                if (!data.enabled || !data.configured || !data.publicKey) {
                    return;
                }
                return getSwRegistration().then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (localSub) {
                        if (!localSub) {
                            pushNeedsActivation = true;
                            return;
                        }
                        var endpoint = localSub.endpoint || '';
                        if (endpoint === '') {
                            pushNeedsActivation = true;
                            return;
                        }
                        return fetchJson(urlAdm + '/push-subscribe?endpoint=' + encodeURIComponent(endpoint))
                            .then(function (verify) {
                                if (!verify.endpointRegistered) {
                                    pushNeedsActivation = true;
                                }
                            });
                    });
                });
            })
            .catch(function () {
                pushNeedsActivation = false;
            })
            .then(function () {
                pushCheckDone = true;
            });
    }

    function setPushBannerError(msg) {
        var el = document.getElementById('pwaDashboardPushError');
        if (!el) {
            return;
        }
        if (msg) {
            el.textContent = msg;
            el.classList.remove('d-none');
        } else {
            el.textContent = '';
            el.classList.add('d-none');
        }
    }

    function activatePushFromBanner() {
        setPushBannerError('');
        var buttons = ['btnPwaDashboardPushActivate'];
        buttons.forEach(function (id) {
            var b = document.getElementById(id);
            if (b) {
                b.disabled = true;
            }
        });

        fetchJson(urlAdm + '/push-subscribe')
            .then(function (data) {
                if (!data.publicKey) {
                    throw new Error('Push não configurado no sistema.');
                }
                return getSwRegistration().then(function (registration) {
                    return requestNotificationPermission().then(function () {
                        return createPushSubscription(registration, data.publicKey).then(function (sub) {
                            return persistPushSubscription(registration, sub);
                        });
                    });
                });
            })
            .then(function () {
                pushNeedsActivation = false;
                refreshPwaPromoState();
            })
            .catch(function (err) {
                setPushBannerError(err.message || 'Não foi possível ativar.');
            })
            .finally(function () {
                buttons.forEach(function (id) {
                    var b = document.getElementById(id);
                    if (b) {
                        b.disabled = false;
                    }
                });
            });
    }

    function refreshPwaPromoState() {
        var updateRow = document.getElementById('pwaDashboardUpdateRow');
        var pushRow = document.getElementById('pwaDashboardPushRow');
        var installRow = document.getElementById('pwaDashboardInstallRow');

        if (updateRow) {
            updateRow.classList.add('d-none');
        }
        if (pushRow) {
            pushRow.classList.add('d-none');
        }
        if (installRow) {
            installRow.classList.add('d-none');
        }
        setPushBannerError('');

        if (isUpdatePending() && !isUpdateDeferred()) {
            if (updateRow) {
                updateRow.classList.remove('d-none');
            }
            return;
        }

        if (pushCheckDone && pushNeedsActivation && !isPushBannerDeferred()) {
            if (pushRow) {
                pushRow.classList.remove('d-none');
            }
            return;
        }

        if (!isPwaInstalled()) {
            if (installRow) {
                installRow.classList.remove('d-none');
                applyInstallHintsLayout();
            }
        }
    }

    function openInChromeAndroid() {
        var path = window.location.pathname + window.location.search + window.location.hash;
        var host = window.location.host;
        window.location.href =
            'intent://' + host + path + '#Intent;scheme=https;package=com.android.chrome;end';
    }

    function dismissBrowserHint() {
        try {
            localStorage.setItem(STORAGE_HINT, String(Date.now()));
        } catch (e) { /* ignore */ }
    }

    function wasBrowserHintDismissedRecently() {
        try {
            var ts = parseInt(localStorage.getItem(STORAGE_HINT) || '0', 10);
            return ts > 0 && (Date.now() - ts) < 7 * 24 * 60 * 60 * 1000;
        } catch (e) {
            return false;
        }
    }

    function showModal(id) {
        var el = document.getElementById(id);
        if (!el || typeof bootstrap === 'undefined') {
            return;
        }
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    function applyInstallHintsLayout() {
        var onIos = isIOS();
        var onIosSafari = isIOSSafari();

        ['pwaDashboardInstallDesktopHint', 'pwaInstallDesktopHint'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            if (onIos) {
                el.classList.add('d-none');
            } else {
                el.classList.remove('d-none');
            }
        });

        ['pwaDashboardInstallIosHint', 'pwaInstallIosHint'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.classList.remove('d-none');
            if (onIosSafari) {
                el.classList.remove('text-muted');
                el.classList.add('alert', 'alert-info', 'py-2', 'px-3', 'mb-0', 'small');
            } else {
                el.classList.remove('alert', 'alert-info', 'py-2', 'px-3');
                if (!onIos) {
                    el.classList.add('text-muted');
                }
            }
        });

        var dashBtn = document.getElementById('btnPwaDashboardInstall');
        var profileBtn = document.getElementById('btnPwaInstall');
        var iosBtnLabel = '<i class="fab fa-apple me-1"></i>Como instalar no iPhone';
        var defaultBtnLabel = '<i class="fas fa-download me-1"></i>Instalar aplicativo';

        if (onIos && !canUseNativeInstallPrompt()) {
            if (dashBtn) {
                dashBtn.innerHTML = iosBtnLabel;
            }
            if (profileBtn) {
                profileBtn.innerHTML = iosBtnLabel;
            }
        } else {
            if (dashBtn) {
                dashBtn.innerHTML = defaultBtnLabel;
            }
            if (profileBtn) {
                profileBtn.innerHTML = defaultBtnLabel;
            }
        }
    }

    function setInstallStatus(text, badgeClass) {
        var statusEl = document.getElementById('pwaInstallStatus');
        if (!statusEl) {
            return;
        }
        statusEl.innerHTML = '<span class="badge ' + badgeClass + '">' + text + '</span>';
    }

    function refreshInstallUi() {
        applyInstallHintsLayout();

        var btn = document.getElementById('btnPwaInstall');
        var leadEl = document.getElementById('pwaInstallLead');
        var hintsBlock = document.getElementById('pwaInstallHintsBlock');
        if (!btn) {
            refreshPwaPromoState();
            return;
        }

        if (isPwaInstalled()) {
            setInstallStatus('Instalado', 'bg-success');
            btn.classList.add('d-none');
            if (leadEl) {
                leadEl.textContent = 'O aplicativo está instalado neste dispositivo. Use o ícone na tela inicial para abrir.';
            }
            if (hintsBlock) {
                hintsBlock.classList.add('d-none');
            }
            checkPushNeedsActivation().then(refreshPwaPromoState);
            return;
        }

        if (hintsBlock) {
            hintsBlock.classList.remove('d-none');
        }

        setInstallStatus('Não instalado', 'bg-secondary');

        if (canUseNativeInstallPrompt()) {
            setInstallStatus('Pronto para instalar', 'bg-primary');
            if (leadEl) {
                leadEl.textContent = 'Toque em Instalar aplicativo para abrir o assistente do navegador.';
            }
            refreshPwaPromoState();
            return;
        }

        if (isEdge() && !isIOS()) {
            setInstallStatus('Instalar pelo Edge', 'bg-primary');
            if (leadEl) {
                leadEl.textContent = 'Use o ícone Instalar na barra de endereço ou ⋯ → Aplicativos. O botão abaixo mostra o passo a passo.';
            }
            refreshPwaPromoState();
            return;
        }

        if (isIOS()) {
            setInstallStatus('Safari — Tela de Início', 'bg-info text-dark');
            if (leadEl) {
                leadEl.textContent = 'No iPhone/iPad a instalação é manual pelo Safari. Siga os passos abaixo ou toque no botão para ver o guia.';
            }
            refreshPwaPromoState();
            return;
        }

        if (isFirefox() || (/Android/i.test(ua()) && !isChromeAndroid())) {
            setInstallStatus('Use o Chrome', 'bg-warning text-dark');
            if (leadEl) {
                leadEl.textContent = 'Para instalar com suporte completo, abra o portal no Google Chrome neste aparelho.';
            }
            refreshPwaPromoState();
            return;
        }

        if (leadEl) {
            leadEl.textContent = 'Instale o portal na tela inicial para abrir como aplicativo, com ícone próprio e melhor experiência em celular.';
        }
        refreshPwaPromoState();
    }

    function triggerAppUpdate() {
        if (swRegistration && swRegistration.waiting) {
            swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
    }

    function hideInstallButtons() {
        ['btnPwaInstall', 'btnPwaDashboardInstall'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.classList.add('d-none');
            }
        });
    }

    function showInstallFallbackModal() {
        if (isIOS() || isIOSSafari()) {
            showModal('pwaInstallSafariModal');
            return;
        }
        if (isFirefox() || (/Android/i.test(ua()) && !isChromeAndroid())) {
            if (!wasBrowserHintDismissedRecently()) {
                showModal('pwaInstallChromeModal');
                return;
            }
            showModal('pwaInstallGenericModal');
            return;
        }
        if (isEdge()) {
            showModal('pwaInstallEdgeModal');
            return;
        }
        showModal('pwaInstallGenericModal');
    }

    function handleInstallClick() {
        if (isPwaInstalled()) {
            showModal('pwaInstallAlreadyModal');
            return;
        }

        var btn = document.getElementById('btnPwaDashboardInstall') || document.getElementById('btnPwaInstall');
        if (btn) {
            btn.disabled = true;
        }

        waitForInstallPrompt(2000).then(function (hasPrompt) {
            if (hasPrompt) {
                return runNativeInstallPrompt();
            }
            showInstallFallbackModal();
        }).finally(function () {
            if (btn) {
                btn.disabled = false;
            }
        });
    }

    function bindClickOnce(id, handler) {
        var el = document.getElementById(id);
        if (!el || el._pwaBound) {
            return;
        }
        el._pwaBound = true;
        el.addEventListener('click', handler);
    }

    function initInstallHandlers() {
        bindClickOnce('btnPwaInstall', handleInstallClick);
        bindClickOnce('btnPwaDashboardInstall', handleInstallClick);
        bindClickOnce('btnPwaOpenChrome', openInChromeAndroid);
        bindClickOnce('btnPwaDismissChromeHint', dismissBrowserHint);

        syncDeferredInstallPrompt();
        refreshInstallUi();
    }

    function initPushBannerHandlers() {
        bindClickOnce('btnPwaDashboardPushActivate', activatePushFromBanner);
        bindClickOnce('btnPwaDashboardPushLater', deferPushBanner);
    }

    function initUpdateHandlers() {
        bindClickOnce('btnPwaDashboardUpdate', triggerAppUpdate);
        bindClickOnce('btnPwaDashboardUpdateLater', deferUpdate);
    }

    function initUpdateChecker() {
        if (!('serviceWorker' in navigator)) {
            checkPushNeedsActivation().then(refreshPwaPromoState);
            return;
        }

        var swUrl = getServiceWorkerUrl();

        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (window._admsPwaReloading) {
                return;
            }
            window._admsPwaReloading = true;
            window.location.reload();
        });

        navigator.serviceWorker.register(swUrl).then(function (registration) {
            swRegistration = registration;

            registration.addEventListener('updatefound', function () {
                var newWorker = registration.installing;
                if (!newWorker) {
                    return;
                }
                newWorker.addEventListener('statechange', function () {
                    if (
                        newWorker.state === 'installed' &&
                        navigator.serviceWorker.controller
                    ) {
                        refreshPwaPromoState();
                    }
                });
            });

            setInterval(function () {
                registration.update().then(function () {
                    refreshPwaPromoState();
                }).catch(function () {});
            }, 60 * 60 * 1000);

            return checkPushNeedsActivation();
        }).then(function () {
            refreshPwaPromoState();
        }).catch(function () {
            checkPushNeedsActivation().then(refreshPwaPromoState);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initUpdateChecker();
        initUpdateHandlers();
        initPushBannerHandlers();
        initInstallHandlers();
    });
})();
