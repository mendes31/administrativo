/**
 * Menu lateral: scroll até o item ativo e accordion entre grupos irmãos.
 */
(function () {
    'use strict';

    var root = '#layoutSidenav_nav';
    var scrollTimers = [];
    var userBrowsingMenu = false;

    function getSidenavMenu() {
        return document.querySelector(root + ' .sb-sidenav-menu');
    }

    function clearScrollTimers() {
        scrollTimers.forEach(function (id) {
            clearTimeout(id);
        });
        scrollTimers = [];
    }

    function schedule(fn, delay) {
        scrollTimers.push(setTimeout(fn, delay));
    }

    function getCollapseTrigger(collapseEl) {
        if (!collapseEl || !collapseEl.id) {
            return null;
        }
        return document.querySelector(root + ' [data-bs-target="#' + collapseEl.id + '"]');
    }

    function getSiblingContainer(collapseEl) {
        var parentSel = collapseEl.getAttribute('data-bs-parent');
        if (!parentSel) {
            return null;
        }
        return document.querySelector(parentSel);
    }

    function isLeafLink(link) {
        if (!link) {
            return false;
        }
        var href = link.getAttribute('href');
        return Boolean(href && href !== '#' && link.getAttribute('data-bs-toggle') !== 'collapse');
    }

    function clearActiveInSubtree(container, exceptEl) {
        if (!container) {
            return;
        }
        container.querySelectorAll('.nav-link.active').forEach(function (link) {
            if (exceptEl && link === exceptEl) {
                return;
            }
            link.classList.remove('active');
            if (link.getAttribute('data-bs-toggle') === 'collapse') {
                link.classList.add('collapsed');
                link.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function closeDirectSiblingCollapses(collapseEl) {
        var container = getSiblingContainer(collapseEl);
        if (!container) {
            return;
        }

        Array.from(container.children).forEach(function (child) {
            if (!child.classList.contains('collapse') || child === collapseEl) {
                return;
            }

            child.classList.remove('show');
            child.classList.remove('collapsing');

            var trigger = getCollapseTrigger(child);
            if (trigger) {
                trigger.classList.add('collapsed');
                trigger.classList.remove('active');
                trigger.setAttribute('aria-expanded', 'false');
            }

            child.querySelectorAll('.nav-link.active').forEach(function (link) {
                if (isLeafLink(link)) {
                    link.classList.remove('active');
                }
            });
        });
    }

    function activateGroupTrigger(trigger) {
        if (!trigger) {
            return;
        }
        trigger.classList.remove('collapsed');
        trigger.classList.add('active');
        trigger.setAttribute('aria-expanded', 'true');
    }

    function handleGroupExpand(collapseEl, trigger) {
        userBrowsingMenu = true;
        clearScrollTimers();

        var container = getSiblingContainer(collapseEl);
        closeDirectSiblingCollapses(collapseEl);
        clearActiveInSubtree(container, trigger);

        collapseEl.classList.add('show');
        collapseEl.classList.remove('collapsing');
        activateGroupTrigger(trigger);

        var sidenavMenu = getSidenavMenu();
        if (sidenavMenu && trigger) {
            var menuRect = sidenavMenu.getBoundingClientRect();
            var linkRect = trigger.getBoundingClientRect();
            var relativeTop = linkRect.top - menuRect.top + sidenavMenu.scrollTop;
            var targetTop = relativeTop - sidenavMenu.clientHeight / 2 + linkRect.height / 2;
            targetTop = Math.max(0, Math.min(targetTop, sidenavMenu.scrollHeight - sidenavMenu.clientHeight));
            sidenavMenu.scrollTo({ top: targetTop, behavior: 'smooth' });
        }
    }

    function handleGroupCollapse(collapseEl, trigger) {
        userBrowsingMenu = true;
        clearScrollTimers();
        collapseEl.classList.remove('show');
        collapseEl.classList.remove('collapsing');
        trigger.classList.add('collapsed');
        trigger.classList.remove('active');
        trigger.setAttribute('aria-expanded', 'false');
    }

    function isNestedModuleGroup(collapseEl) {
        var container = getSiblingContainer(collapseEl);
        return Boolean(container && container.id && container.id.indexOf('nav-collapse') === 0);
    }

    function setupSiblingAccordion() {
        var sidenavMenu = getSidenavMenu();
        if (!sidenavMenu) {
            return;
        }

        sidenavMenu.addEventListener('click', function (event) {
            var leaf = event.target.closest('a.nav-link');
            if (leaf && isLeafLink(leaf)) {
                userBrowsingMenu = false;
                return;
            }

            var trigger = event.target.closest('[data-bs-toggle="collapse"]');
            if (!trigger || !sidenavMenu.contains(trigger)) {
                return;
            }

            var targetSel = trigger.getAttribute('data-bs-target');
            if (!targetSel) {
                return;
            }

            var targetCollapse = document.querySelector(targetSel);
            if (!targetCollapse || !isNestedModuleGroup(targetCollapse)) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            if (targetCollapse.classList.contains('show')) {
                handleGroupCollapse(targetCollapse, trigger);
            } else {
                handleGroupExpand(targetCollapse, trigger);
            }
        }, true);

        sidenavMenu.addEventListener('shown.bs.collapse', function (event) {
            var collapseEl = event.target;
            if (!collapseEl.classList.contains('collapse') || !sidenavMenu.contains(collapseEl)) {
                return;
            }

            if (!userBrowsingMenu) {
                return;
            }

            var trigger = getCollapseTrigger(collapseEl);
            closeDirectSiblingCollapses(collapseEl);
            clearActiveInSubtree(getSiblingContainer(collapseEl), trigger);
            activateGroupTrigger(trigger);
        });
    }

    function getActiveLeafLink() {
        var links = document.querySelectorAll(root + ' a.nav-link.active');
        var result = null;

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            if (isLeafLink(link)) {
                result = link;
            }
        }

        return result;
    }

    function expandCollapseAncestors(activeLink) {
        if (userBrowsingMenu) {
            return;
        }

        var sidenavMenu = getSidenavMenu();
        if (!sidenavMenu || !activeLink) {
            return;
        }

        var current = activeLink.parentElement;
        while (current && current !== sidenavMenu) {
            if (current.classList.contains('collapse')) {
                if (!current.classList.contains('show')) {
                    current.classList.add('show');
                }
                activateGroupTrigger(getCollapseTrigger(current));
            }
            current = current.parentElement;
        }
    }

    function scrollActiveIntoMenuCenter() {
        if (userBrowsingMenu) {
            return;
        }

        var sidenavMenu = getSidenavMenu();
        var activeLink = getActiveLeafLink();
        if (!sidenavMenu || !activeLink) {
            return;
        }

        expandCollapseAncestors(activeLink);

        var menuRect = sidenavMenu.getBoundingClientRect();
        var linkRect = activeLink.getBoundingClientRect();
        var relativeTop = linkRect.top - menuRect.top + sidenavMenu.scrollTop;
        var targetTop = relativeTop - sidenavMenu.clientHeight / 2 + linkRect.height / 2;
        targetTop = Math.max(0, Math.min(targetTop, sidenavMenu.scrollHeight - sidenavMenu.clientHeight));

        sidenavMenu.scrollTo({
            top: targetTop,
            behavior: 'smooth'
        });
    }

    function scheduleInitialScroll() {
        schedule(scrollActiveIntoMenuCenter, 80);
        schedule(scrollActiveIntoMenuCenter, 320);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupSiblingAccordion();
        scheduleInitialScroll();
    });
})();
