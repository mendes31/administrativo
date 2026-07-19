<?php

declare(strict_types=1);

/**
 * Layout público do portal de vagas (sem menu administrativo).
 *
 * @var string $title
 * @var string $view
 * @var string $base_url
 * @var string $url_adm
 */
$title = (string) ($title ?? 'Vagas abertas');
$view = (string) ($view ?? 'list');
$base_url = rtrim((string) ($base_url ?? ''), '/');
$url_adm = rtrim((string) ($url_adm ?? ''), '/') . '/';
$viewFile = __DIR__ . '/' . $view . '.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — Trabalhe conosco</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8') ?>public/adms/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($url_adm, ENT_QUOTES, 'UTF-8') ?>public/adms/fontawesome/css/all.min.css">
    <style>
        :root {
            --vp-bg: #f3f5f7;
            --vp-ink: #1c2430;
            --vp-accent: #0f5c4c;
            --vp-accent-soft: #e6f2ef;
            --vp-muted: #5b6573;
            --vp-card: #ffffff;
            --vp-line: #d9dee5;
        }
        body.vp-body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(15, 92, 76, 0.12), transparent 40%),
                linear-gradient(180deg, #eef2f4 0%, var(--vp-bg) 40%, #e8ecef 100%);
            color: var(--vp-ink);
            font-family: "Segoe UI", system-ui, sans-serif;
        }
        .vp-shell {
            max-width: 960px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
        }
        .vp-brand {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--vp-line);
        }
        .vp-brand h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--vp-accent);
        }
        .vp-brand p {
            margin: 0.25rem 0 0;
            color: var(--vp-muted);
            font-size: 0.95rem;
        }
        .vp-card {
            background: var(--vp-card);
            border: 1px solid var(--vp-line);
            border-radius: 12px;
            padding: 1.25rem 1.35rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(28, 36, 48, 0.04);
        }
        .vp-card h2 {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
        }
        .vp-meta {
            color: var(--vp-muted);
            font-size: 0.9rem;
        }
        .vp-badge {
            display: inline-block;
            background: var(--vp-accent-soft);
            color: var(--vp-accent);
            border-radius: 999px;
            padding: 0.15rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .vp-link {
            color: var(--vp-accent);
            font-weight: 600;
            text-decoration: none;
        }
        .vp-link:hover { text-decoration: underline; }
        .vp-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--vp-muted);
        }
        .vp-footer {
            margin-top: 2rem;
            text-align: center;
            color: var(--vp-muted);
            font-size: 0.85rem;
        }
        .vp-prose p { margin-bottom: 0.75rem; white-space: pre-wrap; }
        .vp-search .form-control {
            border-radius: 8px;
            border-color: var(--vp-line);
        }
        .vp-search .btn {
            background: var(--vp-accent);
            border-color: var(--vp-accent);
        }
    </style>
</head>
<body class="vp-body">
    <div class="vp-shell">
        <header class="vp-brand">
            <div>
                <h1>Trabalhe conosco</h1>
                <p>Vagas abertas para candidatura</p>
            </div>
            <a class="vp-link" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">Todas as vagas</a>
        </header>

        <?php
        if (is_file($viewFile)) {
            require $viewFile;
        } else {
            echo '<div class="vp-empty">Conteúdo indisponível.</div>';
        }
        ?>

        <footer class="vp-footer">
            Listagem pública somente leitura. A candidatura online será disponibilizada em breve.
        </footer>
    </div>
</body>
</html>
