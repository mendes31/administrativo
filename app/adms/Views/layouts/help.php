<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}
$title = htmlspecialchars((string) ($this->data['title_head'] ?? 'Manual do sistema'), ENT_QUOTES, 'UTF-8');
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
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
        .help-sidebar { width: 280px; max-width: 100%; border-right: 1px solid #dee2e6; background: #f8f9fa; }
        .help-sidebar .nav-link { font-size: .9rem; padding: .35rem .75rem; color: #333; }
        .help-sidebar .nav-link.active { background: #e7f5ef; color: #1a6b47; font-weight: 600; }
        .help-sidebar .module-title { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; padding: .75rem .75rem .25rem; }
        .help-sidebar .module-title.is-hidden { display: none; }
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
            .help-sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #dee2e6; max-height: 40vh; overflow-y: auto; }
        }
    </style>
</head>
<body class="bg-white">
<div class="d-flex help-layout">
    <aside class="help-sidebar py-2">
        <div class="px-3 pb-2 border-bottom mb-2">
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
        <p id="help-search-empty" class="help-search-empty mb-0">Nenhum tópico encontrado.</p>
        <nav class="nav flex-column" id="help-manual-nav">
            <a class="nav-link help-nav-item<?= ($this->data['topic_id'] ?? '') === 'index' ? ' active' : '' ?>"
               href="<?= htmlspecialchars($urlAdm) ?>/context-help"
               data-help-module="geral"
               data-help-search="início manual geral">Início</a>
            <?php
            $currentModule = '';
            foreach ($this->data['topics'] ?? [] as $t):
                $moduleTitle = (string) ($t['module_title'] ?? '');
                $moduleId = (string) ($t['module_id'] ?? '');
                if ($moduleTitle !== $currentModule):
                    $currentModule = $moduleTitle;
                    if ($currentModule !== ''): ?>
                        <div class="module-title help-module-title" data-help-module="<?= htmlspecialchars($moduleId) ?>">
                            <?= htmlspecialchars($currentModule) ?>
                        </div>
                    <?php endif;
                endif;
                $tid = (string) ($t['id'] ?? '');
                $topicTitle = (string) ($t['title'] ?? $tid);
                $active = ($this->data['topic_id'] ?? '') === $tid ? ' active' : '';
                $searchBlob = strtolower($topicTitle . ' ' . $moduleTitle . ' ' . $tid);
            ?>
            <a class="nav-link help-nav-item<?= $active ?>"
               href="<?= htmlspecialchars($urlAdm) ?>/context-help/<?= rawurlencode($tid) ?>"
               data-help-module="<?= htmlspecialchars($moduleId) ?>"
               data-help-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($topicTitle) ?>
            </a>
            <?php endforeach; ?>
        </nav>
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

        var searchInput = document.getElementById('help-manual-search');
        var emptyMsg = document.getElementById('help-search-empty');
        if (!searchInput) {
            return;
        }

        function normalize(text) {
            return (text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function applyHelpSearch() {
            var q = normalize(searchInput.value.trim());
            var items = document.querySelectorAll('#help-manual-nav .help-nav-item');
            var modules = document.querySelectorAll('#help-manual-nav .help-module-title');
            var visibleCount = 0;
            var moduleVisible = {};

            items.forEach(function (link) {
                var blob = normalize(link.getAttribute('data-help-search') || link.textContent);
                var match = q === '' || blob.indexOf(q) !== -1;
                link.classList.toggle('is-hidden', !match);
                if (match) {
                    visibleCount++;
                    var mod = link.getAttribute('data-help-module') || '';
                    moduleVisible[mod] = true;
                }
            });

            modules.forEach(function (title) {
                var mod = title.getAttribute('data-help-module') || '';
                var show = q === '' || moduleVisible[mod];
                title.classList.toggle('is-hidden', !show);
            });

            if (emptyMsg) {
                emptyMsg.classList.toggle('is-visible', q !== '' && visibleCount === 0);
            }
        }

        searchInput.addEventListener('input', applyHelpSearch);
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                applyHelpSearch();
                searchInput.blur();
            }
        });
    })();
    </script>
</body>
</html>
