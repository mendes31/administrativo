<?php
/** @var string $mode choice|result */
$mode = $mode ?? 'result';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($heading ?? 'Portaria', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/public/adms/css/bootstrap.min.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="bg-light">
<div class="container px-3 py-4 py-md-5" style="max-width: 520px;">
<?php if ($mode === 'choice'): ?>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= htmlspecialchars((string) ($heading ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mb-1"><strong><?= htmlspecialchars((string) ($visitante ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php if (!empty($documento)): ?>
                <p class="small text-muted mb-1">Documento: <?= htmlspecialchars((string) $documento, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <p class="small text-muted mb-1">Protocolo: <?= htmlspecialchars((string) ($protocolo ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="small text-muted mb-1">Período: <?= htmlspecialchars((string) ($periodo ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($motivo)): ?>
                <p class="mb-3">Motivo: <?= htmlspecialchars((string) $motivo, ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <p class="mb-3"></p>
            <?php endif; ?>
            <p class="mb-3">Deseja autorizar a visita?</p>
            <div class="d-grid gap-2">
                <a class="btn btn-success btn-lg" href="<?= htmlspecialchars((string) ($autorizar_url ?? '#'), ENT_QUOTES, 'UTF-8') ?>">Autorizar</a>
                <a class="btn btn-outline-danger btn-lg" href="<?= htmlspecialchars((string) ($recusar_url ?? '#'), ENT_QUOTES, 'UTF-8') ?>">Recusar</a>
            </div>
            <p class="small text-muted mt-3 mb-0">Não é necessário fazer login. Este link é exclusivo desta solicitação.</p>
        </div>
    </div>
<?php else: ?>
    <div class="alert <?= !empty($success) ? 'alert-success' : 'alert-secondary' ?> shadow-sm" role="status">
        <h1 class="h5"><?= htmlspecialchars((string) ($heading ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="mb-0"><?= nl2br(htmlspecialchars((string) ($message ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
<?php endif; ?>
</div>
</body>
</html>
