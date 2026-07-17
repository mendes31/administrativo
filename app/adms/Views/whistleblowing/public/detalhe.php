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
$responseDeadline = strtotime((string)($report['reporter_response_deadline'] ?? ''));
$isResponseOverdue = $responseDeadline !== false && $responseDeadline < time();
?>
<div class="canal-card canal-card--wide">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Protocolo <?php echo htmlspecialchars((string)($report['protocol'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
            <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars((string)($report['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary">Sair</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-1 g-sm-2 mb-2 mb-sm-3 small text-muted canal-meta">
        <div class="col-12 col-sm-4"><strong>Classificação:</strong> <?php echo htmlspecialchars((string)($report['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="col-12 col-sm-4"><strong>Risco:</strong> <?php echo htmlspecialchars((string)($report['risk_level'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="col-12 col-sm-4"><strong>Registrada:</strong> <?php echo date('d/m/Y H:i', strtotime((string)($report['created_at'] ?? 'now'))); ?></div>
    </div>

    <div class="alert alert-info py-2 small mb-2 mb-sm-3">
        <i class="fas fa-info-circle me-1"></i>
        As respostas do comitê aparecem abaixo. Consulte esta página periodicamente com protocolo e senha — não enviamos retornos por e-mail ou telefone.
    </div>

    <?php if (($report['status'] ?? '') !== 'Encerrada' && $responseDeadline !== false): ?>
    <div class="alert alert-<?php echo $isResponseOverdue ? 'danger' : 'warning'; ?> py-2 small mb-2 mb-sm-3">
        <i class="fas fa-<?php echo $isResponseOverdue ? 'exclamation-circle' : 'clock'; ?> me-1"></i>
        <?php if ($isResponseOverdue): ?>
            O prazo para seu retorno venceu em <strong><?php echo date('d/m/Y H:i', $responseDeadline); ?></strong>.
            O protocolo permanece aberto até a decisão do comitê. Você ainda pode enviar informações enquanto ele não for encerrado.
        <?php else: ?>
            O comitê aguarda seu retorno até <strong><?php echo date('d/m/Y H:i', $responseDeadline); ?></strong>.
            Responda abaixo, mesmo que seja apenas para informar que ainda está reunindo os dados.
        <?php endif; ?>
    </div>
    <?php endif; ?>

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
    <?php
    $ownAttachments = array_values(array_filter($attachments, static fn ($a) => ($a['uploaded_by'] ?? '') === 'denunciante'));
    $committeeAttachments = array_values(array_filter($attachments, static fn ($a) => ($a['uploaded_by'] ?? '') === 'comite'));
    ?>
    <?php if ($ownAttachments !== []): ?>
    <div class="mb-3">
        <h2 class="h6 fw-semibold">Seus anexos</h2>
        <ul class="list-group list-group-flush border rounded">
            <?php foreach ($ownAttachments as $att): ?>
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
    <?php if ($committeeAttachments !== []): ?>
    <div class="mb-3">
        <h2 class="h6 fw-semibold">Anexos do comitê</h2>
        <ul class="list-group list-group-flush border rounded">
            <?php foreach ($committeeAttachments as $att): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center small">
                    <span><?php echo htmlspecialchars((string)($att['original_name'] ?? 'arquivo'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo htmlspecialchars($base_url . 'download-anexo?id=' . (int)($att['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                       class="btn btn-outline-primary btn-sm py-0">
                        <i class="fas fa-download"></i>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (($report['status'] ?? '') !== 'Encerrada'): ?>
    <div class="border-top pt-3 mt-3">
        <h2 class="h6 fw-semibold">Enviar nova informação</h2>
        <form class="canal-form" method="post" action="<?php echo htmlspecialchars($base_url . 'responder', ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="protocol" value="<?php echo htmlspecialchars((string)($protocol ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="password" value="<?php echo htmlspecialchars((string)($password ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-2">
                <textarea name="message" class="form-control" rows="3" placeholder="Informações adicionais..."></textarea>
            </div>
            <div class="mb-2 canal-attachments-field">
                <input type="file" name="attachments[]" class="canal-attachments-input" multiple
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.ogg,.opus,.mp4,.doc,.docx"
                    style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;"
                    tabindex="-1" aria-hidden="true">
                <button type="button" class="btn btn-outline-primary btn-sm canal-attachments-add">
                    <i class="fas fa-paperclip me-1"></i>Adicionar arquivos
                </button>
                <div class="form-text small mt-1">
                    Máx. <?= htmlspecialchars(\App\adms\Models\Services\WhistleblowingUploadService::maxFileSizeLabel(), ENT_QUOTES, 'UTF-8') ?> —
                    PDF, imagem, MP3/WAV/OGG ou MP4. Use o botão várias vezes para acrescentar.
                </div>
                <div class="canal-attachment-list mt-2"></div>
                <div class="canal-attachment-feedback alert alert-danger py-2 small mt-2 d-none" role="alert"></div>
            </div>
            <div class="canal-form-actions">
            <button type="submit" class="btn btn-canal-primary">
                <i class="fas fa-reply me-1"></i>Enviar
            </button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-success mt-3 mb-3">
        <h2 class="h6 fw-semibold mb-2"><i class="fas fa-check-circle me-1"></i> Protocolo encerrado</h2>
        <p class="mb-1">
            Esta denúncia foi encerrada
            <?php if (!empty($report['closed_at'])): ?>
                em <strong><?php echo date('d/m/Y H:i', strtotime((string)$report['closed_at'])); ?></strong>
            <?php endif; ?>.
        </p>
        <?php if (!empty($report['closure_outcome'])): ?>
            <p class="mb-1"><strong>Resultado:</strong> <?php echo htmlspecialchars((string)$report['closure_outcome'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if (!empty($report['closure_reason'])): ?>
            <div class="mb-2"><strong>Explicação:</strong> <?php echo nl2br(htmlspecialchars((string)$report['closure_reason'], ENT_QUOTES, 'UTF-8')); ?></div>
        <?php endif; ?>
        <p class="mb-0">
            O histórico continua disponível para consulta. Se surgirem <strong>fatos novos</strong>, novas evidências ou se a situação persistir,
            registre uma nova denúncia e informe este protocolo anterior no relato.
        </p>
    </div>
    <a href="<?php echo htmlspecialchars($base_url . 'registrar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-outline">
        <i class="fas fa-file-alt me-1"></i>Registrar nova denúncia
    </a>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/_attachment_validation.php'; ?>
