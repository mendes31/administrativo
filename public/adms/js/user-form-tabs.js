/**
 * Persistência da aba ativa no formulário de usuário (create/update).
 * Mantém ?tab= e sessionStorage para sobreviver a refresh e pós-salvar.
 * Não usa hash (#tab-*) porque coincide com o id dos painéis e o navegador
 * rola a página para o topo do conteúdo ao trocar de aba.
 */
(function () {
    'use strict';

    var ALLOWED = ['usuario', 'pessoais', 'endereco', 'contratuais', 'formacoes', 'acessos', 'permissoes'];
    var savedScrollY = window.scrollY || window.pageYOffset || 0;

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
            // Sem hash: #tab-usuario aponta para o painel e força scroll vertical.
            window.history.replaceState(null, '', url.pathname + url.search);
        } catch (e) {
            /* ignore */
        }
    }

    function updateSaveButtons(key) {
        var permsSave = document.getElementById('btnSaveUserPermissions');
        var isPerms = key === 'permissoes';
        // Salvar do cadastro permanece em todas as abas (mesmo comportamento).
        // Em Permissões, o botão específico de ACL aparece além do Salvar.
        if (permsSave) {
            permsSave.classList.toggle('d-none', !isPerms);
        }
    }

    function persistTab(key) {
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
        updateSaveButtons(key);
    }

    function restorePageScroll() {
        window.scrollTo(0, savedScrollY);
    }

    function activateTab(key, useBootstrap) {
        key = normalize(key) || 'usuario';
        persistTab(key);

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

    /**
     * Mantém a aba ativa visível na faixa horizontal, sem alterar o scroll vertical da página.
     * Usa getBoundingClientRect porque offsetLeft é relativo ao offsetParent posicionado
     * (card-body), e não ao contêiner de rolagem das abas.
     */
    function revealActiveTab() {
        document.querySelectorAll('.user-view-tabs-scroll').forEach(function (wrap) {
            var active = wrap.querySelector('.nav-link.active');
            if (!active || wrap.scrollWidth <= wrap.clientWidth + 1) {
                return;
            }

            var wrapRect = wrap.getBoundingClientRect();
            var tabRect = active.getBoundingClientRect();
            var pad = 16;
            var delta = 0;

            if (tabRect.left < wrapRect.left + pad) {
                delta = tabRect.left - wrapRect.left - pad;
            } else if (tabRect.right > wrapRect.right - pad) {
                delta = tabRect.right - wrapRect.right + pad;
            }

            if (delta !== 0) {
                wrap.scrollLeft += delta;
            }
        });
    }

    function init() {
        // Remove hash legado (#tab-*) que fazia o navegador rolar até o painel.
        if (window.location.hash && /^#tab-/.test(window.location.hash)) {
            try {
                var clean = new URL(window.location.href);
                window.history.replaceState(null, '', clean.pathname + clean.search);
            } catch (e) {
                /* ignore */
            }
        }

        revealActiveTab();
        window.addEventListener('resize', revealActiveTab);
        // Ícones/fontes podem alterar a largura das abas depois do DOMContentLoaded.
        window.addEventListener('load', revealActiveTab);
        requestAnimationFrame(revealActiveTab);

        if (!document.getElementById('userFormTabs')) {
            return;
        }

        activateTab(resolveInitialTab(), true);
        // Após ativar a aba inicial, centraliza-a na faixa sem puxar a página.
        requestAnimationFrame(function () {
            revealActiveTab();
            restorePageScroll();
        });

        document.querySelectorAll('#userFormTabs [data-tab-key]').forEach(function (btn) {
            btn.addEventListener('show.bs.tab', function () {
                savedScrollY = window.scrollY || window.pageYOffset || 0;
            });

            btn.addEventListener('shown.bs.tab', function () {
                var key = btn.getAttribute('data-tab-key');
                if (!key) {
                    return;
                }
                persistTab(key);
                // Bootstrap foca o botão e o navegador faz scrollIntoView na página;
                // restaura a posição e só ajusta a rolagem horizontal das abas.
                restorePageScroll();
                requestAnimationFrame(function () {
                    restorePageScroll();
                    revealActiveTab();
                    if (document.activeElement === btn && typeof btn.blur === 'function') {
                        btn.blur();
                    }
                });
            });

            btn.addEventListener('click', function () {
                savedScrollY = window.scrollY || window.pageYOffset || 0;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
