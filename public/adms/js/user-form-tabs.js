/**
 * Persistência da aba ativa no formulário de usuário (create/update).
 * Mantém ?tab=, #hash e sessionStorage para sobreviver a refresh e pós-salvar.
 */
(function () {
    'use strict';

    var ALLOWED = ['usuario', 'pessoais', 'endereco', 'contratuais', 'formacoes'];

    function storageKey() {
        var idEl = document.getElementById('id');
        var id = idEl && idEl.value ? String(idEl.value) : 'new';
        return 'adms_user_form_active_tab_' + id;
    }

    function normalize(raw) {
        if (!raw) {
            return null;
        }
        var key = String(raw).toLowerCase().trim().replace(/^#?tab-/, '');
        return ALLOWED.indexOf(key) >= 0 ? key : null;
    }

    function resolveInitialTab() {
        var params = new URLSearchParams(window.location.search);
        return (
            normalize(params.get('tab')) ||
            normalize(window.location.hash) ||
            normalize(sessionStorage.getItem(storageKey())) ||
            normalize(document.getElementById('user_form_active_tab') && document.getElementById('user_form_active_tab').value) ||
            'usuario'
        );
    }

    function syncUrl(key) {
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            url.hash = 'tab-' + key;
            window.history.replaceState(null, '', url.pathname + url.search + url.hash);
        } catch (e) {
            /* ignore */
        }
    }

    function activateTab(key, useBootstrap) {
        key = normalize(key) || 'usuario';
        var hidden = document.getElementById('user_form_active_tab');
        if (hidden) {
            hidden.value = key;
        }
        try {
            sessionStorage.setItem(storageKey(), key);
        } catch (e) {
            /* ignore */
        }
        syncUrl(key);

        var btn = document.querySelector('#userFormTabs [data-tab-key="' + key + '"]');
        if (!btn) {
            return;
        }

        if (useBootstrap && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(btn).show();
            return;
        }

        document.querySelectorAll('#userFormTabs .nav-link').forEach(function (el) {
            el.classList.remove('active');
            el.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('#userFormTabsContent .tab-pane').forEach(function (el) {
            el.classList.remove('show', 'active');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        var pane = document.querySelector(btn.getAttribute('data-bs-target'));
        if (pane) {
            pane.classList.add('show', 'active');
        }
    }

    function init() {
        if (!document.getElementById('userFormTabs')) {
            return;
        }

        activateTab(resolveInitialTab(), true);

        document.querySelectorAll('#userFormTabs [data-tab-key]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function () {
                var key = btn.getAttribute('data-tab-key');
                if (!key) {
                    return;
                }
                var hidden = document.getElementById('user_form_active_tab');
                if (hidden) {
                    hidden.value = key;
                }
                try {
                    sessionStorage.setItem(storageKey(), key);
                } catch (e) {
                    /* ignore */
                }
                syncUrl(key);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
