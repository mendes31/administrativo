/**
 * Rola a área rolável do menu lateral até centralizar o item ativo.
 * Usa posição em relação ao viewport (getBoundingClientRect): offsetTop
 * de links aninhados em colapses não é relativo a .sb-sidenav-menu.
 */
(function () {
    var root = '#layoutSidenav_nav';

    function getActiveLeafLink() {
        var links = document.querySelectorAll(root + ' a.nav-link.active');
        var result = null;
        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            var href = a.getAttribute('href');
            if (href && href !== '#' && a.getAttribute('data-bs-toggle') !== 'collapse') {
                result = a;
            }
        }
        if (result) {
            return result;
        }

        var path = window.location.pathname.replace(/\/$/, '');
        var navLinks = document.querySelectorAll(root + ' .sb-sidenav-menu a.nav-link[href]');
        for (var j = 0; j < navLinks.length; j++) {
            var link = navLinks[j];
            var href = link.getAttribute('href');
            if (!href || href === '#' || link.getAttribute('data-bs-toggle') === 'collapse') {
                continue;
            }
            try {
                var linkPath = new URL(href, window.location.origin).pathname.replace(/\/$/, '');
                if (path === linkPath || path.endsWith(linkPath)) {
                    link.classList.add('active');
                    return link;
                }
            } catch (e) {
                /* ignora href inválido */
            }
        }
        return null;
    }

    function expandCollapseAncestors(activeLink) {
        var sidenavMenu = document.querySelector(root + ' .sb-sidenav-menu');
        if (!sidenavMenu || !activeLink) {
            return;
        }
        var current = activeLink.parentElement;
        while (current && current !== sidenavMenu) {
            if (current.classList.contains('collapse')) {
                if (!current.classList.contains('show')) {
                    current.classList.add('show');
                }
                if (current.id) {
                    var trigger = document.querySelector(
                        root + ' [data-bs-target="#' + current.id + '"]'
                    );
                    if (trigger) {
                        trigger.classList.remove('collapsed');
                        trigger.classList.add('active');
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                }
            }
            current = current.parentElement;
        }
    }

    function scrollActiveIntoMenuCenter() {
        var sidenavMenu = document.querySelector(root + ' .sb-sidenav-menu');
        var activeLink = getActiveLeafLink();
        if (!sidenavMenu || !activeLink) {
            return;
        }

        expandCollapseAncestors(activeLink);

        var menuRect = sidenavMenu.getBoundingClientRect();
        var linkRect = activeLink.getBoundingClientRect();
        var relativeTop = linkRect.top - menuRect.top + sidenavMenu.scrollTop;
        var targetTop =
            relativeTop - sidenavMenu.clientHeight / 2 + linkRect.height / 2;
        targetTop = Math.max(0, Math.min(targetTop, sidenavMenu.scrollHeight - sidenavMenu.clientHeight));

        sidenavMenu.scrollTo({
            top: targetTop,
            behavior: 'smooth'
        });
    }

    function scheduleScrolls() {
        var activeLink = getActiveLeafLink();
        if (activeLink) {
            expandCollapseAncestors(activeLink);
        }
        scrollActiveIntoMenuCenter();
        setTimeout(scrollActiveIntoMenuCenter, 120);
        setTimeout(scrollActiveIntoMenuCenter, 380);
        setTimeout(scrollActiveIntoMenuCenter, 700);
    }

    document.addEventListener('DOMContentLoaded', function () {
        scheduleScrolls();
        var nav = document.querySelector(root);
        if (nav) {
            nav.querySelectorAll('.collapse').forEach(function (collapseEl) {
                collapseEl.addEventListener('shown.bs.collapse', function () {
                    var link = getActiveLeafLink();
                    if (link && collapseEl.contains(link)) {
                        scrollActiveIntoMenuCenter();
                    }
                });
            });
        }
    });
})();

