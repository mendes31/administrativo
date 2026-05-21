(function () {
    'use strict';

    var cfg = window.__PwaAppInit || {};
    var urlAdm = (cfg.urlAdm || '').replace(/\/$/, '');
    var swVersion = cfg.swVersion || '20260520-13';
    var STORAGE_HINT = 'adms_pwa_browser_hint_dismissed';
    var STORAGE_UPDATE_LATER = 'adms_pwa_update_later';

    var deferredInstallPrompt = null;

    function ua() {
        return navigator.userAgent || '';
    }

    function isIOS() {
        return /iPad|iPhone|iPod/i.test(ua()) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function isFirefox() {
        return /Firefox\//i.test(ua());
    }

    function isChromeAndroid() {
        var u = ua();
        return /Android/i.test(u) &&
            /Chrome/i.test(u) &&
            !/Edg/i.test(u) &&
            !/Firefox/i.test(u) &&
            !/OPR\//i.test(u);
    }

    function isEdge() {
        return /Edg\//i.test(ua());
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
        return !!deferredInstallPrompt;
    }

    function getServiceWorkerUrl() {
        return urlAdm + '/service-worker.js?v=' + encodeURIComponent(swVersion);
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

    function setInstallStatus(text, badgeClass) {
        var statusEl = document.getElementById('pwaInstallStatus');
        if (!statusEl) {
            return;
        }
        statusEl.innerHTML = '<span class="badge ' + badgeClass + '">' + text + '</span>';
    }

    function refreshInstallUi() {
        var btn = document.getElementById('btnPwaInstall');
        var hintEl = document.getElementById('pwaInstallHint');
        if (!btn) {
            return;
        }

        if (isPwaInstalled()) {
            setInstallStatus('Instalado', 'bg-success');
            btn.classList.add('d-none');
            if (hintEl) {
                hintEl.textContent = 'O aplicativo está instalado neste dispositivo. Use o ícone na tela inicial para abrir.';
            }
            return;
        }

        setInstallStatus('Não instalado', 'bg-secondary');

        if (canUseNativeInstallPrompt()) {
            setInstallStatus('Pronto para instalar', 'bg-primary');
            if (hintEl) {
                hintEl.textContent = 'Toque no botão abaixo para ver o pedido de instalação do navegador.';
            }
            return;
        }

        if (isIOS()) {
            setInstallStatus('Instalação manual', 'bg-info text-dark');
            if (hintEl) {
                hintEl.textContent = 'No iPhone/iPad: Safari → Compartilhar → Adicionar à Tela de Início.';
            }
            return;
        }

        if (isFirefox() || (/Android/i.test(ua()) && !isChromeAndroid())) {
            setInstallStatus('Use o Chrome', 'bg-warning text-dark');
            if (hintEl) {
                hintEl.textContent = 'Para instalar com suporte completo, recomendamos o Google Chrome neste aparelho.';
            }
            return;
        }

        if (hintEl) {
            hintEl.textContent = 'Melhor compatibilidade: Google Chrome (Android/PC). Edge no Windows também costuma funcionar. Se não aparecer o pedido automático, use o menu do navegador (⋮) → Instalar aplicativo.';
        }
    }

    function handleInstallClick() {
        if (isPwaInstalled()) {
            showModal('pwaInstallAlreadyModal');
            return;
        }

        if (canUseNativeInstallPrompt()) {
            deferredInstallPrompt.prompt();
            deferredInstallPrompt.userChoice.then(function (choice) {
                if (choice.outcome === 'accepted') {
                    setInstallStatus('Instalado', 'bg-success');
                    var btn = document.getElementById('btnPwaInstall');
                    if (btn) {
                        btn.classList.add('d-none');
                    }
                }
                deferredInstallPrompt = null;
                refreshInstallUi();
            });
            return;
        }

        if (isIOS()) {
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

        showModal('pwaInstallGenericModal');
    }

    function initInstallSection() {
        var btn = document.getElementById('btnPwaInstall');
        if (!btn) {
            return;
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredInstallPrompt = e;
            refreshInstallUi();
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            refreshInstallUi();
        });

        btn.addEventListener('click', handleInstallClick);

        var chromeBtn = document.getElementById('btnPwaOpenChrome');
        if (chromeBtn) {
            chromeBtn.addEventListener('click', function () {
                openInChromeAndroid();
            });
        }

        var dismissBtn = document.getElementById('btnPwaDismissChromeHint');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', dismissBrowserHint);
        }

        refreshInstallUi();
    }

    function showUpdateBanner(registration) {
        var banner = document.getElementById('pwaUpdateBanner');
        if (!banner) {
            return;
        }

        var laterTs = 0;
        try {
            laterTs = parseInt(sessionStorage.getItem(STORAGE_UPDATE_LATER) || '0', 10);
        } catch (e) { /* ignore */ }
        if (laterTs > 0 && (Date.now() - laterTs) < 60 * 60 * 1000) {
            return;
        }

        banner.classList.remove('d-none');

        var btnUpdate = document.getElementById('btnPwaUpdateNow');
        var btnLater = document.getElementById('btnPwaUpdateLater');

        if (btnUpdate && !btnUpdate._bound) {
            btnUpdate._bound = true;
            btnUpdate.addEventListener('click', function () {
                if (registration.waiting) {
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                }
            });
        }

        if (btnLater && !btnLater._bound) {
            btnLater._bound = true;
            btnLater.addEventListener('click', function () {
                try {
                    sessionStorage.setItem(STORAGE_UPDATE_LATER, String(Date.now()));
                } catch (e) { /* ignore */ }
                banner.classList.add('d-none');
            });
        }
    }

    function initUpdateChecker() {
        if (!('serviceWorker' in navigator)) {
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
            if (registration.waiting && navigator.serviceWorker.controller) {
                showUpdateBanner(registration);
            }

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
                        showUpdateBanner(registration);
                    }
                });
            });

            setInterval(function () {
                registration.update().catch(function () {});
            }, 60 * 60 * 1000);
        }).catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        initUpdateChecker();
        initInstallSection();
    });
})();
