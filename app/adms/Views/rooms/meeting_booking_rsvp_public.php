<?php
/** @var string $mode choice|result|conflict */
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
            <?php if (!empty($my_calendar_url)): ?>
                <p class="small text-muted mt-3 mb-0"><a href="<?= htmlspecialchars($my_calendar_url, ENT_QUOTES, 'UTF-8'); ?>">Ver o meu calendário</a> antes de confirmar.</p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($mode === 'conflict'): ?>
    <div class="card shadow-sm border-warning">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= htmlspecialchars($heading ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="mb-2">Ao aceitar <strong><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></strong> (<?= htmlspecialchars($room ?? '', ENT_QUOTES, 'UTF-8'); ?>,
                <?= htmlspecialchars($start ?? '', ENT_QUOTES, 'UTF-8'); ?> – <?= htmlspecialchars($end ?? '', ENT_QUOTES, 'UTF-8'); ?>), sobrepõe-se a outros compromissos já confirmados na sua agenda:</p>
            <ul class="small mb-3">
                <?php foreach (($overlaps ?? []) as $o): ?>
                    <li><?= htmlspecialchars((string)($o['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        <span class="text-muted">(<?= htmlspecialchars((string)($o['start'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</span></li>
                <?php endforeach; ?>
            </ul>
            <p class="small text-muted mb-3">Pode recusar, rever o <a href="<?= htmlspecialchars((string)($my_calendar_url ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">seu calendário</a>, ou aceitar na mesma se for intencional.</p>
            <div class="d-grid gap-2">
                <a class="btn btn-warning" href="<?= htmlspecialchars($accept_force_url ?? '#', ENT_QUOTES, 'UTF-8'); ?>">Aceitar na mesma (confirmo o conflito)</a>
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
