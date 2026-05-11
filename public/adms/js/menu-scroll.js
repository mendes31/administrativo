/**
 * Rola a área rolável do menu lateral até centralizar o item ativo.
 * Usa posição em relação ao viewport (getBoundingClientRect): offsetTop
 * de links aninhados em colapses não é relativo a .sb-sidenav-menu.
 */
(function () {
    var root = '#layoutSidenav_nav';

    function getActiveLeafLink() {
        var links = document.querySelectorAll(root + ' a.nav-link.active');
        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            var href = a.getAttribute('href');
            if (href && href !== '#' && a.getAttribute('data-bs-toggle') !== 'collapse') {
                return a;
            }
        }
        return null;
    }

    function scrollActiveIntoMenuCenter() {
        var sidenavMenu = document.querySelector(root + ' .sb-sidenav-menu');
        var activeLink = getActiveLeafLink();
        if (!sidenavMenu || !activeLink) {
            return;
        }

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
        scrollActiveIntoMenuCenter();
        setTimeout(scrollActiveIntoMenuCenter, 120);
        setTimeout(scrollActiveIntoMenuCenter, 380);
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

