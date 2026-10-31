<?php
/** @var array<string, mixed> $invite */
/** @var array<string, mixed> $event */
/** @var string $image_url */
/** @var bool $open */
/** @var string $excerpt */
/** @var string $notice */
/** @var string $csrf */
/** @var bool $missing */
$missing = !empty($missing);
$title = $missing ? 'Convite não encontrado' : (string) ($event['title'] ?? 'Convite');
$status = (string) ($invite['status'] ?? '');
$statusLabel = match ($status) {
    'confirmed' => 'Presença confirmada',
    'declined' => 'Presença recusada',
    default => 'Aguardando resposta',
};
$fmt = static function ($value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }
    $ts = strtotime($raw);

    return $ts === false ? $raw : date('d/m/Y H:i', $ts);
};
$urlAdm = $_ENV['URL_ADM'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php if (!$missing): ?>
        <meta property="og:title" content="<?php echo htmlspecialchars('Convite: ' . $title, ENT_QUOTES, 'UTF-8'); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($excerpt !== '' ? $excerpt : 'Confirme ou recuse sua presença.', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($image_url !== ''): ?>
            <meta property="og:image" content="<?php echo htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8'); ?>public/adms/css/bootstrap.min.css">
    <style>
        body { background: #e8f5e9; margin: 0; }
        .invite-wrap { max-width: 520px; margin: 1.5rem auto; padding: 0 1rem 2rem; }
        .invite-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 28px rgba(0,0,0,.08); }
        .invite-hero { background: #1b7a3a; color: #fff; padding: 1.25rem 1.25rem .9rem; }
        .invite-hero img { width: 100%; max-height: 240px; object-fit: cover; border-radius: 12px; display: block; margin-bottom: .9rem; background: #145c2c; }
        .invite-body { padding: 1.1rem 1.25rem 1.35rem; }
    </style>
</head>
<body>
<div class="invite-wrap">
    <div class="invite-card">
        <?php if ($missing): ?>
            <div class="invite-hero">
                <h1 class="h4 mb-0">Convite não encontrado</h1>
            </div>
            <div class="invite-body">
                <p class="mb-0">Este link não corresponde a um convite ativo. Peça um novo convite a quem organizou o evento.</p>
            </div>
        <?php else: ?>
            <div class="invite-hero">
                <?php if ($image_url !== ''): ?>
                    <img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Imagem do evento">
                <?php endif; ?>
                <div class="small text-white-50">Convite</div>
                <h1 class="h4 mb-1"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="small">
                    <?php echo htmlspecialchars($fmt($event['starts_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                    —
                    <?php echo htmlspecialchars($fmt($event['ends_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php if (!empty($event['location'])): ?>
                    <div class="small mt-1"><?php echo htmlspecialchars((string) $event['location'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
            <div class="invite-body">
                <p class="mb-2">Olá, <strong><?php echo htmlspecialchars((string) ($invite['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
                <?php if (!empty($invite['invite_reason'])): ?>
                    <p class="mb-2"><span class="text-muted">Motivo do convite:</span> <?php echo htmlspecialchars((string) $invite['invite_reason'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if ($excerpt !== ''): ?>
                    <p class="mb-3"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if ($notice !== ''): ?>
                    <div class="alert alert-info py-2"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <p class="mb-3">
                    <span class="badge <?php echo $status === 'confirmed' ? 'bg-success' : ($status === 'declined' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                        <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </p>
                <?php if ($open): ?>
                    <form method="post" class="d-flex flex-column flex-sm-row gap-2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="btn btn-success" type="submit" name="answer" value="confirmed">Confirmar presença</button>
                        <button class="btn btn-outline-danger" type="submit" name="answer" value="declined">Recusar</button>
                    </form>
                    <p class="text-muted small mt-3 mb-0">Você pode alterar a resposta até o fim do evento. Este link não pede login.</p>
                <?php else: ?>
                    <p class="text-muted small mb-0">O evento já encerrou. A resposta fica registrada como está.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
