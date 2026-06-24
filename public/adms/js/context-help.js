/**
 * Ajuda de contexto — tecla F1 e link no rodapé (estilo SAP Business One).
 */
(function () {
    'use strict';

    function getPageMenuSlug() {
        if (typeof window.__admsPageMenu === 'string' && window.__admsPageMenu !== '') {
            return window.__admsPageMenu;
        }
        var meta = document.querySelector('meta[name="adms-page-menu"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    /** Aba ativa (nav-tabs) ou seção visível (data-adms-help-section). */
    function getActiveHelpTabSection() {
        var activeTab = document.querySelector('.nav-tabs .nav-link.active[data-adms-help-tab]');
        if (activeTab) {
            return activeTab.getAttribute('data-adms-help-tab') || '';
        }

        var sections = document.querySelectorAll('[data-adms-help-section]');
        if (sections.length === 0) {
            return '';
        }

        var best = null;
        var bestTop = -Infinity;
        var threshold = Math.min(window.innerHeight * 0.45, 320);

        sections.forEach(function (el) {
            var rect = el.getBoundingClientRect();
            if (rect.bottom < 80) {
                return;
            }
            if (rect.top <= threshold && rect.top > bestTop) {
                bestTop = rect.top;
                best = el;
            }
        });

        if (best) {
            return best.getAttribute('data-adms-help-section') || '';
        }

        return sections[0].getAttribute('data-adms-help-section') || '';
    }

    function buildHelpUrl() {
        var base = (typeof window.__admsHelpBaseUrl === 'string' && window.__admsHelpBaseUrl !== '')
            ? window.__admsHelpBaseUrl
            : '';
        if (base === '') {
            return '';
        }
        var slug = getPageMenuSlug();
        var url = slug !== '' ? (base + '/' + encodeURIComponent(slug)) : base;
        var section = getActiveHelpTabSection();
        if (section !== '') {
            url += '#' + encodeURIComponent(section);
        }
        return url;
    }

    function openContextHelp() {
        var url = buildHelpUrl();
        if (url === '') {
            return;
        }

        var link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        try {
            window.open(url, '_blank', 'noopener,noreferrer');
        } catch (e) { /* ignore */ }
    }

    function syncFooterHelpLink() {
        document.querySelectorAll('a.adms-context-help-link').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                openContextHelp();
            });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'F1') {
            return;
        }
        event.preventDefault();
        openContextHelp();
    });

    document.addEventListener('DOMContentLoaded', syncFooterHelpLink);

    window.AdmsContextHelp = {
        open: openContextHelp,
        buildUrl: buildHelpUrl,
        getActiveTabSection: getActiveHelpTabSection
    };
})();
