<?php
$report = $this->data['report'] ?? [];
$statusBadges = [
    'Recebida' => 'secondary', 'Em triagem' => 'info', 'Em análise' => 'primary',
    'Comitê' => 'warning', 'Investigação' => 'warning', 'Providências' => 'info', 'Encerrada' => 'success',
];
$canReply = in_array('WhistleblowingReplyReport', $this->data['buttonPermission'] ?? []);
$canStatus = in_array('WhistleblowingUpdateStatus', $this->data['buttonPermission'] ?? []);
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">
            <i class="fas fa-shield-alt me-2"></i><?php echo htmlspecialchars((string)($report['protocol'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>denuncias">Denúncias</a></li>
            <li class="breadcrumb-item">Detalhe</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-light shadow mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Relato</span>
                    <span class="badge bg-<?php echo $statusBadges[$report['status'] ?? ''] ?? 'secondary'; ?>">
                        <?php echo htmlspecialchars((string)($report['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 small">
                        <div class="col-md-3"><strong>Classificação:</strong> <?php echo htmlspecialchars((string)($report['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Risco:</strong> <?php echo htmlspecialchars((string)($report['risk_level'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Comitê:</strong> <?php echo htmlspecialchars((string)($report['committee_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Responsável:</strong> <?php echo htmlspecialchars((string)($report['assigned_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Registrada:</strong> <?php echo date('d/m/Y H:i', strtotime((string)($report['created_at'] ?? 'now'))); ?></div>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-semibold">Descrição</h6>
                        <div class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars((string)($report['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                    <?php if (!empty($report['involved'])): ?>
                    <div class="mb-0">
                        <h6 class="fw-semibold">Envolvidos</h6>
                        <div class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars((string)$report['involved'], ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-light shadow mb-3">
                <div class="card-header">Mensagens</div>
                <div class="card-body" style="max-height: 420px; overflow-y: auto;">
                    <?php if (empty($this->data['messages'])): ?>
                        <p class="text-muted mb-0">Nenhuma mensagem ainda.</p>
                    <?php else: ?>
                        <?php foreach ($this->data['messages'] as $msg): ?>
                            <?php
                            $isInternal = !empty($msg['is_internal_note']);
                            $isComite = ($msg['sender_type'] ?? '') === 'comite';
                            $bg = $isInternal ? 'border-warning bg-warning bg-opacity-10' : ($isComite ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary');
                            ?>
                            <div class="border rounded p-2 mb-2 <?php echo $bg; ?>">
                                <div class="small text-muted mb-1">
                                    <?php if ($isInternal): ?><i class="fas fa-lock me-1"></i>Nota interna — <?php endif; ?>
                                    <?php echo htmlspecialchars((string)($msg['user_name'] ?? ($isComite ? 'Comitê' : 'Denunciante')), ENT_QUOTES, 'UTF-8'); ?>
                                    — <?php echo date('d/m/Y H:i', strtotime((string)($msg['created_at'] ?? 'now'))); ?>
                                </div>
                                <div><?php echo nl2br(htmlspecialchars((string)($msg['message'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canReply): ?>
            <div class="card border-light shadow mb-3">
                <div class="card-header">Responder</div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>reply-denuncia/<?php echo (int)($report['id'] ?? 0); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($this->data['csrf_reply'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-2">
                            <textarea name="message" class="form-control" rows="4" required placeholder="Resposta visível ao denunciante..."></textarea>
                        </div>
                        <div class="mb-2 form-check">
                            <input type="checkbox" name="is_internal_note" class="form-check-input" id="is_internal_note">
                            <label class="form-check-label" for="is_internal_note">Nota interna (não visível ao denunciante)</label>
                        </div>
                        <div class="mb-2">
                            <input type="file" name="attachments[]" class="form-control form-control-sm" multiple>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Enviar</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if ($canStatus): ?>
            <div class="card border-light shadow mb-3">
                <div class="card-header">Gestão</div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>update-denuncia-status/<?php echo (int)($report['id'] ?? 0); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($this->data['csrf_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-2">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach ($this->data['statuses'] as $st): ?>
                                    <option value="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($report['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Risco</label>
                            <select name="risk_level" class="form-select form-select-sm">
                                <?php foreach ($this->data['risk_levels'] as $risk): ?>
                                    <option value="<?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($report['risk_level'] ?? '') === $risk) ? 'selected' : ''; ?>><?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Responsável</label>
                            <select name="assigned_user_id" class="form-select form-select-sm">
                                <option value="">—</option>
                                <?php foreach ($this->data['users'] as $u): ?>
                                    <option value="<?php echo (int)$u['id']; ?>" <?php echo ((int)($report['assigned_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$u['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Observação (linha do tempo)</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm w-100">Atualizar</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['attachments'])): ?>
            <div class="card border-light shadow mb-3">
                <div class="card-header">Anexos</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($this->data['attachments'] as $att): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                            <span><?php echo htmlspecialchars((string)($att['original_name'] ?? 'arquivo'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/download-attachment/<?php echo (int)$att['id']; ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="fas fa-download"></i></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card border-light shadow mb-3">
                <div class="card-header">Linha do tempo</div>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($this->data['status_log'] ?? [] as $log): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars((string)($log['to_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="text-muted"><?php echo date('d/m/Y H:i', strtotime((string)($log['created_at'] ?? 'now'))); ?>
                                <?php if (!empty($log['user_name'])): ?> — <?php echo htmlspecialchars((string)$log['user_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            </div>
                            <?php if (!empty($log['notes'])): ?><div><?php echo htmlspecialchars((string)$log['notes'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card border-light shadow">
                <div class="card-header">Auditoria de acesso</div>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($this->data['access_log'] ?? [] as $al): ?>
                        <li class="list-group-item">
                            <?php echo htmlspecialchars((string)($al['user_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            — <?php echo htmlspecialchars((string)($al['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="text-muted"><?php echo date('d/m/Y H:i', strtotime((string)($al['created_at'] ?? 'now'))); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
