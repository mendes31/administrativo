<?php

declare(strict_types=1);

/**
 * Layout público do portal de vagas / pré-admissão (sem menu administrativo).
 *
 * @var string $title
 * @var string $view
 * @var string $base_url
 * @var string $url_adm
 * @var string|null $brand_title
 * @var string|null $brand_subtitle
 * @var string|null $brand_link_label
 * @var string|null $brand_link_href
 * @var string|null $footer_text
 * @var bool $skip_brand_link
 */
$title = (string) ($title ?? 'Vagas abertas');
$view = (string) ($view ?? 'list');
$base_url = rtrim((string) ($base_url ?? ''), '/');
$url_adm = rtrim((string) ($url_adm ?? ''), '/') . '/';
$brandTitle = (string) ($brand_title ?? 'Trabalhe conosco');
$brandSubtitle = (string) ($brand_subtitle ?? 'Vagas abertas para candidatura');
$brandLinkLabel = (string) ($brand_link_label ?? 'Todas as vagas');
$brandLinkHref = (string) ($brand_link_href ?? $base_url);
$footerText = (string) ($footer_text ?? 'Portal público de vagas. Dados pessoais tratados conforme o termo LGPD apresentado na candidatura.');
$skipBrandLink = !empty($skip_brand_link);
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
            max-width: 720px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
        }
        .vp-shell-wide { max-width: 960px; }
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
        article.vp-card.vp-card-recebido {
            background: #d9f2f8 !important;
            border-color: #7ec8d9 !important;
            border-left: 4px solid #0dcaf0;
        }
        article.vp-card.vp-card-aprovado {
            background: #d8f3df !important;
            border-color: #8fd4a4 !important;
            border-left: 4px solid #198754;
        }
        article.vp-card.vp-card-recusado {
            background: #f8d7da !important;
            border-color: #f1aeb5 !important;
            border-left: 4px solid #dc3545;
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
        .vp-badge-muted {
            background: #eef1f4;
            color: var(--vp-muted);
        }
        .vp-badge-ok {
            background: #e6f6ea;
            color: #1b7a3d;
        }
        .vp-badge-warn {
            background: #fff4e5;
            color: #9a5b00;
        }
        .vp-badge-danger {
            background: #fdeceb;
            color: #b42318;
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
        .vp-search .form-control,
        .vp-form .form-control {
            border-radius: 8px;
            border-color: var(--vp-line);
        }
        .vp-search .btn,
        .vp-btn-primary {
            background: var(--vp-accent);
            border-color: var(--vp-accent);
            color: #fff;
        }
        .vp-btn-primary:hover {
            background: #0c4a3d;
            border-color: #0c4a3d;
            color: #fff;
        }
        .vp-btn-outline {
            background: #fff;
            border: 1px solid var(--vp-line);
            color: var(--vp-ink);
            border-radius: 8px;
            padding: 0.4rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .vp-btn-outline:hover {
            border-color: var(--vp-accent);
            color: var(--vp-accent);
        }
        .vp-label-caps {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--vp-muted);
            margin-bottom: 0.25rem;
        }
        .vp-alert {
            border-radius: 10px;
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .vp-alert-ok {
            background: #e6f6ea;
            color: #1b7a3d;
            border: 1px solid #b7e4c7;
        }
        .vp-alert-warn {
            background: #fff4e5;
            color: #9a5b00;
            border: 1px solid #f5d9a8;
        }
        .vp-file-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid var(--vp-line);
        }
        .vp-file-name {
            flex: 1 1 10rem;
            min-width: 0;
            font-size: 0.85rem;
            color: var(--vp-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .vp-sent {
            font-size: 0.875rem;
            color: #1b7a3d;
            margin: 0.5rem 0 0;
        }
    </style>
</head>
<body class="vp-body">
    <div class="vp-shell<?= $view === 'list' || $view === 'view' ? ' vp-shell-wide' : '' ?>">
        <header class="vp-brand">
            <div>
                <h1><?= htmlspecialchars($brandTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($brandSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php if (!$skipBrandLink && $brandLinkHref !== ''): ?>
                <a class="vp-link" href="<?= htmlspecialchars($brandLinkHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($brandLinkLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </header>

        <?php
        if (is_file($viewFile)) {
            require $viewFile;
        } else {
            echo '<div class="vp-empty">Conteúdo indisponível.</div>';
        }
        ?>

        <footer class="vp-footer">
            <?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?>
        </footer>
    </div>
</body>
</html>
