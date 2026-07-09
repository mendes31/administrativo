<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string $csrf_token */
/** @var string $protocol */
/** @var string $password */
/** @var array<string, mixed> $report */
/** @var list<array<string, mixed>> $messages */
/** @var list<array<string, mixed>> $attachments */

$statusLabels = [
    'Recebida' => 'secondary',
    'Em triagem' => 'info',
    'Em análise' => 'primary',
    'Comitê' => 'warning',
    'Investigação' => 'warning',
    'Providências' => 'info',
    'Encerrada' => 'success',
];
$badge = $statusLabels[$report['status'] ?? ''] ?? 'secondary';
?>
<div class="canal-card" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Protocolo <?php echo htmlspecialchars((string)($report['protocol'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
            <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars((string)($report['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary">Sair</a>
    </div>

    <div class="row g-2 mb-3 small text-muted">
        <div class="col-md-4"><strong>Classificação:</strong> <?php echo htmlspecialchars((string)($report['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="col-md-4"><strong>Risco:</strong> <?php echo htmlspecialchars((string)($report['risk_level'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="col-md-4"><strong>Registrada em:</strong> <?php echo date('d/m/Y H:i', strtotime((string)($report['created_at'] ?? 'now'))); ?></div>
    </div>

    <div class="mb-3">
        <h2 class="h6 fw-semibold">Seu relato</h2>
        <div class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars((string)($report['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
    </div>

    <?php if (!empty($report['involved'])): ?>
    <div class="mb-3">
        <h2 class="h6 fw-semibold">Envolvidos informados</h2>
        <div class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars((string)$report['involved'], ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
    <div class="mb-3">
        <h2 class="h6 fw-semibold">Comunicação com o comitê</h2>
        <div class="msg-thread">
            <?php foreach ($messages as $msg): ?>
                <?php $isComite = ($msg['sender_type'] ?? '') === 'comite'; ?>
                <div class="msg-item <?php echo $isComite ? 'msg-comite' : 'msg-denunciante'; ?>">
                    <div class="small text-muted mb-1">
                        <?php echo $isComite ? '<i class="fas fa-building me-1"></i>Comitê' : '<i class="fas fa-user-secret me-1"></i>Você'; ?>
                        — <?php echo date('d/m/Y H:i', strtotime((string)($msg['created_at'] ?? 'now'))); ?>
                    </div>
                    <div><?php echo nl2br(htmlspecialchars((string)($msg['message'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($attachments)): ?>
    <div class="mb-3">
        <h2 class="h6 fw-semibold">Seus anexos</h2>
        <ul class="list-group list-group-flush border rounded">
            <?php foreach ($attachments as $att): ?>
                <?php if (($att['uploaded_by'] ?? '') !== 'denunciante') { continue; } ?>
                <li class="list-group-item d-flex justify-content-between align-items-center small">
                    <span><?php echo htmlspecialchars((string)($att['original_name'] ?? 'arquivo'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo htmlspecialchars($base_url . 'download-anexo?id=' . (int)($att['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                       class="btn btn-outline-secondary btn-sm py-0">
                        <i class="fas fa-download"></i>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (($report['status'] ?? '') !== 'Encerrada'): ?>
    <div class="border-top pt-3 mt-3">
        <h2 class="h6 fw-semibold">Enviar nova informação</h2>
        <form method="post" action="<?php echo htmlspecialchars($base_url . 'responder', ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="protocol" value="<?php echo htmlspecialchars((string)($protocol ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="password" value="<?php echo htmlspecialchars((string)($password ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-2">
                <textarea name="message" class="form-control" rows="3" placeholder="Informações adicionais..."></textarea>
            </div>
            <div class="mb-2">
                <input type="file" name="attachments[]" class="form-control form-control-sm" multiple
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.mp4,.doc,.docx">
            </div>
            <button type="submit" class="btn btn-canal-primary btn-sm">
                <i class="fas fa-reply me-1"></i>Enviar
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-success mt-3 mb-0">
        <i class="fas fa-check me-1"></i> Esta denúncia foi encerrada.
    </div>
    <?php endif; ?>
</div>
