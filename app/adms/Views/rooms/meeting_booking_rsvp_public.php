<?php
/** @var string $mode choice|result */
$mode = $mode ?? 'result';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($heading ?? 'Convite', ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container px-3 py-4 py-md-5" style="max-width: 520px;">
<?php if ($mode === 'choice'): ?>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= htmlspecialchars($heading ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="mb-2"><strong><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p class="text-muted small mb-3">Sala: <?= htmlspecialchars($room ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
                Início: <?= htmlspecialchars($start ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
                Fim: <?= htmlspecialchars($end ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="mb-4">Confirme a sua presença:</p>
            <div class="d-grid gap-2">
                <a class="btn btn-success" href="<?= htmlspecialchars($accept_url ?? '#', ENT_QUOTES, 'UTF-8'); ?>">Aceitar</a>
                <a class="btn btn-outline-danger" href="<?= htmlspecialchars($decline_url ?? '#', ENT_QUOTES, 'UTF-8'); ?>">Recusar</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert <?= !empty($success) ? 'alert-success' : 'alert-secondary'; ?> shadow-sm" role="status">
        <h1 class="h5"><?= htmlspecialchars($heading ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="mb-0"><?= nl2br(htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
    </div>
<?php endif; ?>
</div>
</body>
</html>
