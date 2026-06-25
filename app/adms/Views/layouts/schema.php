<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

$title = htmlspecialchars((string) ($this->data['title_head'] ?? 'Biblioteca — Base de dados'), ENT_QUOTES, 'UTF-8');
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
        .schema-layout { min-height: 100vh; background: #f4f6f8; }
        .schema-topbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: .55rem 1rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .schema-topbar-title {
            font-size: .95rem;
            font-weight: 600;
            color: #343a40;
        }
        .schema-topbar-hint {
            font-size: .78rem;
            color: #6c757d;
        }
        .schema-main { padding-bottom: 2rem; }
    </style>
</head>
<body class="schema-layout">
<header class="schema-topbar">
    <div>
        <a href="<?= htmlspecialchars($urlAdm) ?>/dashboard" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Voltar ao portal
        </a>
    </div>
    <div class="text-center flex-grow-1">
        <div class="schema-topbar-title">
            <i class="fa-solid fa-database me-1 text-success"></i> Biblioteca — Base de dados
        </div>
        <div class="schema-topbar-hint">Janela dedicada — sem menu lateral do sistema</div>
    </div>
    <div class="text-end" style="min-width: 8rem;">
        <a href="<?= htmlspecialchars($urlAdm) ?>/list-database-tables" class="small text-decoration-none">
            <i class="fa-solid fa-rotate-right"></i> Recarregar
        </a>
    </div>
</header>
<main class="schema-main">
    <?php include $this->view; ?>
</main>
<script src="<?= htmlspecialchars($urlAdm) ?>/public/adms/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars($urlAdm) ?>/public/adms/js/fontawesome.js"></script>
</body>
</html>
