<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

use App\adms\Helpers\ContextHelpHelper;

$title = htmlspecialchars((string) ($this->data['title_head'] ?? 'Manual do sistema'), ENT_QUOTES, 'UTF-8');
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
$currentTopicId = (string) ($this->data['topic_id'] ?? '');
$helpMenu = $this->data['help_menu'] ?? ContextHelpHelper::loadHelpMenuNav();
$helpModules = $helpMenu['modules'] ?? [];

/**
 * Verifica se o tópico ativo está em algum nó da árvore.
 *
 * @param list<array<string, mixed>> $nodes
 */
function helpNavContainsTopic(array $nodes, string $topicId): bool
{
    foreach ($nodes as $node) {
        if (($node['type'] ?? '') === 'topic' && ($node['topic_id'] ?? '') === $topicId) {
            return true;
        }
        if (($node['type'] ?? '') === 'group' && helpNavContainsTopic($node['children'] ?? [], $topicId)) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<array<string, mixed>> $nodes
 */
function renderHelpNavNodes(
    array $nodes,
    string $moduleId,
    string $moduleTitle,
    string $currentTopicId,
    string $urlAdm,
    int $depth = 0,
    string $pathPrefix = ''
): void {
    foreach ($nodes as $index => $node) {
        $type = (string) ($node['type'] ?? '');
        if ($type === 'topic') {
            $topicId = (string) ($node['topic_id'] ?? '');
            $topicTitle = (string) ($node['title'] ?? $topicId);
            $active = $currentTopicId === $topicId ? ' active' : '';
            $href = $topicId === 'index'
                ? $urlAdm . '/context-help'
                : $urlAdm . '/context-help/' . rawurlencode($topicId);
            $searchBlob = strtolower(trim($pathPrefix . ' ' . $topicTitle . ' ' . $moduleTitle . ' ' . $moduleId . ' ' . $topicId));
            $pad = 1 + ($depth * 0.65);
            ?>
            <a class="nav-link help-nav-item<?= $active ?>"
               href="<?= htmlspecialchars($href) ?>"
               data-help-module="<?= htmlspecialchars($moduleId) ?>"
               data-help-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>"
               style="padding-left: <?= $pad ?>rem;">
                <?= htmlspecialchars($topicTitle) ?>
            </a>
            <?php
            continue;
        }

        if ($type !== 'group') {
            continue;
        }

        $label = (string) ($node['label'] ?? 'Grupo');
        $children = $node['children'] ?? [];
        if (!is_array($children) || $children === []) {
            continue;
        }

        $childPath = trim($pathPrefix . ' ' . $label);
        $groupId = 'help-sub-' . htmlspecialchars($moduleId) . '-' . $depth . '-' . $index;
        $hasActive = helpNavContainsTopic($children, $currentTopicId);
        $expandedClass = $hasActive ? ' is-expanded' : '';
        $activeClass = $hasActive ? ' has-active-topic' : '';
        $pad = 0.75 + ($depth * 0.65);
        ?>
        <div class="help-submenu-group<?= $expandedClass . $activeClass ?>" data-help-depth="<?= (int) $depth ?>">
            <button type="button"
                    class="help-submenu-toggle"
                    style="padding-left: <?= $pad ?>rem;"
                    aria-expanded="<?= $hasActive ? 'true' : 'false' ?>"
                    aria-controls="<?= $groupId ?>">
                <span><?= htmlspecialchars($label) ?></span>
                <i class="fas fa-chevron-right help-submenu-chevron" aria-hidden="true"></i>
            </button>
            <div class="help-submenu-body" id="<?= $groupId ?>">
                <?php renderHelpNavNodes($children, $moduleId, $moduleTitle, $currentTopicId, $urlAdm, $depth + 1, $childPath); ?>
            </div>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="shortcut icon" href="<?= htmlspecialchars($urlAdm) ?>/public/adms/image/icon/favicon.ico">
    <link href="<?= htmlspecialchars($urlAdm) ?>/public/adms/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($urlAdm) ?>/public/adms/css/styles_admin.css" rel="stylesheet">
    <style>
        .help-layout { min-height: 100vh; }
        .help-sidebar {
            width: 300px;
            max-width: 100%;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            max-height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
        }
        .help-sidebar-header { flex-shrink: 0; }
        .help-sidebar-nav-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .help-module-group { border-bottom: 1px solid #e9ecef; }
        .help-module-group.is-hidden,
        .help-submenu-group.is-hidden { display: none; }
        .help-module-group.has-active-topic .help-module-toggle,
        .help-submenu-group.has-active-topic > .help-submenu-toggle {
            color: #1a6b47;
            background: #f0faf4;
        }
        .help-module-toggle,
        .help-submenu-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
        }
        .help-module-toggle {
            padding: .55rem .75rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #495057;
        }
        .help-submenu-toggle {
            padding: .4rem .75rem .4rem 1rem;
            font-size: .82rem;
            font-weight: 600;
            color: #343a40;
        }
        .help-module-toggle:hover,
        .help-submenu-toggle:hover { background: #e9ecef; }
        .help-module-toggle:focus-visible,
        .help-submenu-toggle:focus-visible {
            outline: 2px solid #1a6b47;
            outline-offset: -2px;
        }
        .help-module-chevron,
        .help-submenu-chevron {
            font-size: .65rem;
            color: #6c757d;
            transition: transform .2s ease;
            flex-shrink: 0;
        }
        .help-module-group.is-expanded .help-module-chevron,
        .help-submenu-group.is-expanded .help-submenu-chevron { transform: rotate(90deg); }
        .help-module-body,
        .help-submenu-body { display: none; padding-bottom: .2rem; }
        .help-module-group.is-expanded > .help-module-body,
        .help-submenu-group.is-expanded > .help-submenu-body { display: block; }
        .help-sidebar .nav-link {
            font-size: .84rem;
            padding: .35rem .75rem .35rem 1rem;
            color: #333;
            border-left: 3px solid transparent;
            border-radius: 0 .25rem .25rem 0;
            margin-right: .35rem;
        }
        .help-sidebar .nav-link:hover {
            background: #eef2f5;
            color: #1a6b47;
        }
        .help-sidebar .nav-link.active {
            background: linear-gradient(90deg, #1a6b47 0%, #22855a 100%);
            color: #fff !important;
            font-weight: 600;
            border-left-color: #0b3d28;
            box-shadow: 0 2px 8px rgba(26, 107, 71, .35);
        }
        .help-sidebar .nav-link.is-hidden { display: none; }
        .help-search-empty { display: none; font-size: .85rem; color: #6c757d; padding: .5rem .75rem; }
        .help-search-empty.is-visible { display: block; }
        .help-content { flex: 1; padding: 1.5rem 2rem; max-width: 960px; }
        .help-content h1 { font-size: 1.6rem; margin-bottom: 1rem; }
        .help-content h2 { font-size: 1.2rem; margin-top: 1.5rem; }
        .help-content h3 { font-size: 1.05rem; margin-top: 1rem; }
        .help-note { background: #f1f3f5; border-left: 4px solid #6c757d; padding: .75rem 1rem; margin: 1rem 0; }
        .help-field { margin-bottom: .75rem; }
        .help-field strong { display: block; }
        .help-breadcrumb { font-size: .85rem; color: #6c757d; margin-bottom: .5rem; }
        .help-figure { margin: 1.25rem 0 1.5rem; }
        .help-screenshot {
            display: block;
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            background: #fff;
        }
        .help-figure figcaption {
            margin-top: .5rem;
            font-size: .85rem;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .help-layout { flex-direction: column; }
            .help-sidebar {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #dee2e6;
                max-height: 40vh;
                position: relative;
            }
        }
    </style>
</head>
<body class="bg-white">
<div class="d-flex help-layout">
    <aside class="help-sidebar py-2">
        <div class="help-sidebar-header px-3 pb-2 border-bottom mb-2">
            <div class="fw-bold"><i class="fas fa-book me-1"></i> Manual do sistema</div>
            <small class="text-muted d-block">Ajuda de contexto (F1) — abre em nova aba</small>
            <div class="mt-2 position-relative">
                <input type="search"
                       id="help-manual-search"
                       class="form-control form-control-sm ps-4"
                       placeholder="Buscar tópico…"
                       autocomplete="off"
                       aria-label="Buscar no manual">
                <i class="fas fa-search position-absolute text-muted" style="left:10px;top:50%;transform:translateY(-50%);font-size:.75rem;pointer-events:none;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="help-sidebar-nav-scroll" id="help-sidebar-nav-scroll">
            <p id="help-search-empty" class="help-search-empty mb-0">Nenhum tópico encontrado.</p>
            <nav class="nav flex-column" id="help-manual-nav" aria-label="Tópicos do manual">
                <?php foreach ($helpModules as $module):
                    $moduleId = (string) ($module['id'] ?? '');
                    $moduleTitle = (string) ($module['title'] ?? $moduleId);
                    $children = $module['children'] ?? [];
                    if ($moduleId === '' || !is_array($children) || $children === []) {
                        continue;
                    }
                    $hasActive = helpNavContainsTopic($children, $currentTopicId);
                    $expandedClass = $hasActive ? ' is-expanded' : '';
                    $activeModuleClass = $hasActive ? ' has-active-topic' : '';
                ?>
                <div class="help-module-group<?= $expandedClass . $activeModuleClass ?>"
                     data-help-module="<?= htmlspecialchars($moduleId) ?>">
                    <button type="button"
                            class="help-module-toggle"
                            aria-expanded="<?= $hasActive ? 'true' : 'false' ?>"
                            aria-controls="help-module-body-<?= htmlspecialchars($moduleId) ?>">
                        <span><?= htmlspecialchars($moduleTitle) ?></span>
                        <i class="fas fa-chevron-right help-module-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="help-module-body" id="help-module-body-<?= htmlspecialchars($moduleId) ?>">
                        <?php renderHelpNavNodes($children, $moduleId, $moduleTitle, $currentTopicId, $urlAdm); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>
    <main class="help-content">
        <?php include $this->view; ?>
    </main>
</div>
    <script src="<?= htmlspecialchars($urlAdm) ?>/public/adms/js/fontawesome.js"></script>
    <script>
    (function () {
        var hash = window.location.hash;
        if (hash && hash.length > 1) {
            var id = decodeURIComponent(hash.slice(1));
            var el = document.getElementById(id);
            if (el) {
                setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100);
            }
        }

        var scroller = document.getElementById('help-sidebar-nav-scroll');
        var searchInput = document.getElementById('help-manual-search');
        var emptyMsg = document.getElementById('help-search-empty');
        var moduleGroups = document.querySelectorAll('.help-module-group');

        function normalize(text) {
            return (text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function setExpanded(el, expanded) {
            el.classList.toggle('is-expanded', expanded);
            var btn = el.querySelector(':scope > .help-module-toggle, :scope > .help-submenu-toggle');
            if (btn) {
                btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            }
        }

        function submenuHasVisibleTopic(group) {
            var links = group.querySelectorAll('.help-nav-item');
            for (var i = 0; i < links.length; i++) {
                if (!links[i].classList.contains('is-hidden')) {
                    return true;
                }
            }
            return false;
        }

        function applySubmenuVisibility(group) {
            var subgroups = group.querySelectorAll(':scope > .help-submenu-body > .help-submenu-group');
            subgroups.forEach(function (sg) {
                applySubmenuVisibility(sg);
                var visible = submenuHasVisibleTopic(sg);
                sg.classList.toggle('is-hidden', !visible);
            });
        }

        function centerActiveSidebarItem() {
            var active = document.querySelector('#help-manual-nav .nav-link.active:not(.is-hidden)');
            if (!scroller || !active) {
                return;
            }
            var scrollerRect = scroller.getBoundingClientRect();
            var activeRect = active.getBoundingClientRect();
            var relativeTop = activeRect.top - scrollerRect.top + scroller.scrollTop;
            scroller.scrollTop = relativeTop - (scroller.clientHeight / 2) + (activeRect.height / 2);
        }

        function expandAncestors(el) {
            var parent = el.parentElement;
            while (parent) {
                if (parent.classList.contains('help-module-group') || parent.classList.contains('help-submenu-group')) {
                    setExpanded(parent, true);
                }
                parent = parent.parentElement;
            }
        }

        function applyHelpSearch() {
            var q = normalize(searchInput ? searchInput.value.trim() : '');
            var visibleCount = 0;

            document.querySelectorAll('.help-nav-item').forEach(function (link) {
                var blob = normalize(link.getAttribute('data-help-search') || link.textContent);
                var match = q === '' || blob.indexOf(q) !== -1;
                link.classList.toggle('is-hidden', !match);
                if (match) {
                    visibleCount++;
                }
            });

            moduleGroups.forEach(function (group) {
                applySubmenuVisibility(group);
                var moduleMatch = submenuHasVisibleTopic(group);
                group.classList.toggle('is-hidden', q !== '' && !moduleMatch);

                if (q !== '') {
                    setExpanded(group, moduleMatch);
                    group.querySelectorAll('.help-submenu-group').forEach(function (sg) {
                        if (!sg.classList.contains('is-hidden')) {
                            setExpanded(sg, true);
                        }
                    });
                } else {
                    var hasActive = !!group.querySelector('.nav-link.active:not(.is-hidden)');
                    setExpanded(group, hasActive);
                    group.querySelectorAll('.help-submenu-group').forEach(function (sg) {
                        var sgActive = !!sg.querySelector('.nav-link.active:not(.is-hidden)');
                        setExpanded(sg, sgActive);
                    });
                }
            });

            if (emptyMsg) {
                emptyMsg.classList.toggle('is-visible', q !== '' && visibleCount === 0);
            }

            if (q === '') {
                setTimeout(centerActiveSidebarItem, 0);
            }
        }

        document.querySelectorAll('.help-module-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.help-module-group');
                if (!group) {
                    return;
                }
                var expanded = !group.classList.contains('is-expanded');
                if (searchInput && normalize(searchInput.value.trim()) === '') {
                    moduleGroups.forEach(function (g) { setExpanded(g, false); });
                }
                setExpanded(group, expanded);
            });
        });

        document.querySelectorAll('.help-submenu-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.help-submenu-group');
                if (!group) {
                    return;
                }
                setExpanded(group, !group.classList.contains('is-expanded'));
            });
        });

        var activeLink = document.querySelector('#help-manual-nav .nav-link.active');
        if (activeLink) {
            expandAncestors(activeLink);
        }

        applyHelpSearch();
        setTimeout(centerActiveSidebarItem, 80);
        window.addEventListener('load', centerActiveSidebarItem);

        if (searchInput) {
            searchInput.addEventListener('input', applyHelpSearch);
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    applyHelpSearch();
                    searchInput.blur();
                }
            });
        }
    })();
    </script>
</body>
</html>
