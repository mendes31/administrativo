<?php
$report = $this->data['report'] ?? [];
$slaService = new \App\adms\Models\Services\WhistleblowingSlaService();
$sla = $slaService->progress($report);
$closureSlaEnabled = $slaService->defaultClosureSlaLabel() !== null;
$closureSla = $closureSlaEnabled ? $slaService->closureProgress($report) : null;
$reporterResponse = (new \App\adms\Models\Services\WhistleblowingReporterInactivityService())->status($report);
$statusBadges = [
    'Recebida' => 'secondary', 'Em triagem' => 'info', 'Em análise' => 'primary',
    'Comitê' => 'warning', 'Investigação' => 'warning', 'Providências' => 'info', 'Encerrada' => 'success',
];
$canReply = in_array('WhistleblowingReplyReport', $this->data['buttonPermission'] ?? []);
$canStatus = in_array('WhistleblowingUpdateStatus', $this->data['buttonPermission'] ?? []);
$statusForm = is_array($this->data['status_form'] ?? null) ? $this->data['status_form'] : [];
$selectedStatus = (string) ($statusForm['status'] ?? $report['status'] ?? '');
$selectedRisk = (string) ($statusForm['risk_level'] ?? $report['risk_level'] ?? '');
$selectedAssignedUser = (int) ($statusForm['assigned_user_id'] ?? $report['assigned_user_id'] ?? 0);
$selectedClosureOutcome = (string) ($statusForm['closure_outcome'] ?? $report['closure_outcome'] ?? '');
$selectedClosureReason = (string) ($statusForm['closure_reason'] ?? $report['closure_reason'] ?? '');
?>

<style>
.wb-report-page {
    max-width: 1120px;
}
.wb-report-page .card {
    border: 0 !important;
    border-radius: 18px;
    overflow: hidden;
}
.wb-report-page .card-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
    padding: 1rem 1.25rem;
}
.wb-report-page .card-body {
    padding: 1.25rem;
}
.wb-report-page .list-group-item {
    padding: .9rem 1.25rem;
}
@media (min-width: 768px) {
    .wb-report-page .card-body {
        padding: 1.5rem;
    }
}
@media (max-width: 575.98px) {
    .wb-report-page .card {
        border-radius: 12px;
    }
    .wb-report-page .card-header,
    .wb-report-page .card-body,
    .wb-report-page .list-group-item {
        padding-left: .85rem;
        padding-right: .85rem;
    }
}
</style>

<div class="container-fluid px-2 px-md-4 mx-auto wb-report-page">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mb-2 text-primary fw-bold mobile-hide-page-title" style="font-size: 1.7rem; letter-spacing: -1px;">
            <i class="fas fa-shield-alt me-2"></i><?php echo htmlspecialchars((string)($report['protocol'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>denuncias">Denúncias</a></li>
            <li class="breadcrumb-item">Detalhe</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-light shadow-sm mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Relato</span>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-<?php echo $statusBadges[$report['status'] ?? ''] ?? 'secondary'; ?>">
                            <?php echo htmlspecialchars((string)($report['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <?php if (($report['status'] ?? '') === 'Encerrada' && !empty($report['closure_outcome'])): ?>
                            <span class="badge bg-dark"><?php echo htmlspecialchars((string)$report['closure_outcome'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 small">
                        <div class="col-md-3"><strong>Classificação:</strong> <?php echo htmlspecialchars((string)($report['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Risco:</strong> <?php echo htmlspecialchars((string)($report['risk_level'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Comitê:</strong> <?php echo htmlspecialchars((string)($report['committee_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Responsável:</strong> <?php echo htmlspecialchars((string)($report['assigned_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong>Registrada:</strong> <?php echo date('d/m/Y H:i', strtotime((string)($report['created_at'] ?? 'now'))); ?></div>
                        <?php if (($report['status'] ?? '') === 'Encerrada'): ?>
                        <div class="col-md-3">
                            <strong>Encerrada em:</strong>
                            <?php echo !empty($report['closed_at'])
                                ? date('d/m/Y H:i', strtotime((string)$report['closed_at']))
                                : '—'; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Resultado:</strong>
                            <?php if (!empty($report['closure_outcome'])): ?>
                                <span class="badge bg-dark"><?php echo htmlspecialchars((string)$report['closure_outcome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Não informado</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <strong>SLA 1ª resposta:</strong>
                            <?php if ($sla['available']): ?>
                                <span title="<?php echo htmlspecialchars($sla['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($sla['deadline'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <div class="progress position-relative mt-1" style="height: 15px; max-width: 260px;" role="progressbar"
                                    aria-label="<?php echo htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-valuenow="<?php echo (int)$sla['percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar progress-bar-striped bg-<?php echo htmlspecialchars($sla['color'], ENT_QUOTES, 'UTF-8'); ?>"
                                        style="width: <?php echo (int)$sla['percent']; ?>%"></div>
                                    <span class="position-absolute w-100 text-center fw-semibold"
                                        style="font-size: .68rem; line-height: 15px; color: <?php echo $sla['percent'] >= 50 ? '#fff' : '#212529'; ?>;">
                                        <?php echo (int)$sla['percent']; ?>% — <?php echo htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Não definido</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($closureSlaEnabled && $closureSla !== null): ?>
                        <div class="col-md-6">
                            <strong>SLA encerramento:</strong>
                            <?php if ($closureSla['available']): ?>
                                <span title="<?php echo htmlspecialchars($closureSla['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($closureSla['deadline'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <div class="progress position-relative mt-1" style="height: 15px; max-width: 300px;" role="progressbar"
                                    aria-label="<?php echo htmlspecialchars($closureSla['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-valuenow="<?php echo (int)$closureSla['percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar progress-bar-striped bg-<?php echo htmlspecialchars($closureSla['color'], ENT_QUOTES, 'UTF-8'); ?>"
                                        style="width: <?php echo (int)$closureSla['percent']; ?>%"></div>
                                    <span class="position-absolute w-100 text-center fw-semibold"
                                        style="font-size: .68rem; line-height: 15px; color: <?php echo $closureSla['percent'] >= 50 ? '#fff' : '#212529'; ?>;">
                                        <?php echo (int)$closureSla['percent']; ?>% — <?php echo htmlspecialchars($closureSla['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Não definido</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($reporterResponse['active']): ?>
                        <div class="col-md-6">
                            <strong>Retorno do denunciante:</strong>
                            <span class="badge bg-<?php echo htmlspecialchars($reporterResponse['color'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($reporterResponse['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            até <?php echo htmlspecialchars($reporterResponse['deadline'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <strong>Denunciante:</strong>
                            <?php if (!empty($report['is_reporter_identified'])): ?>
                                <span class="badge bg-info">Identificação voluntária</span>
                            <?php else: ?>
                                <span class="text-muted">Anônimo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($report['is_reporter_identified'])): ?>
                    <div class="alert alert-info py-2 small mb-3 text-break">
                        <strong>Contato voluntário:</strong>
                        <?php echo htmlspecialchars((string)($report['reporter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($report['reporter_email'])): ?>
                            — <?php echo htmlspecialchars((string)$report['reporter_email'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                        <?php if (!empty($report['reporter_phone'])): ?>
                            — <?php echo htmlspecialchars((string)$report['reporter_phone'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (($report['status'] ?? '') === 'Encerrada' && (!empty($report['closure_outcome']) || !empty($report['closure_reason']))): ?>
                    <div class="alert alert-success py-2 small mb-3 text-break">
                        <strong>Conclusão da apuração:</strong>
                        <?php echo htmlspecialchars((string)($report['closure_outcome'] ?? 'Sem resultado'), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($report['closure_reason'])): ?>
                            <div class="mt-1"><?php echo nl2br(htmlspecialchars((string)$report['closure_reason'], ENT_QUOTES, 'UTF-8')); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($reporterResponse['active'] && $reporterResponse['overdue']): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <strong>Retorno do denunciante vencido.</strong>
                        O protocolo não foi encerrado automaticamente. Avalie o histórico e decida manualmente se há elementos para encerrar,
                        registrando resultado e motivo.
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <h6 class="fw-semibold">Descrição</h6>
                        <div class="border rounded p-3 bg-light text-break"><?php echo nl2br(htmlspecialchars((string)($report['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                    <?php if (!empty($report['involved'])): ?>
                    <div class="mb-0">
                        <h6 class="fw-semibold">Envolvidos</h6>
                        <div class="border rounded p-3 bg-light text-break"><?php echo nl2br(htmlspecialchars((string)$report['involved'], ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-light shadow-sm mb-3">
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
                                <div class="text-break"><?php echo nl2br(htmlspecialchars((string)($msg['message'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canReply): ?>
            <div class="card border-light shadow-sm mb-3">
                <div class="card-header">Responder</div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>reply-denuncia/<?php echo (int)($report['id'] ?? 0); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($this->data['csrf_reply'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-2">
                            <textarea name="message" class="form-control" rows="4" required placeholder="Resposta visível ao denunciante..."></textarea>
                            <div class="form-text">Uma resposta pública inicia ou reinicia o prazo configurado para retorno do denunciante.</div>
                        </div>
                        <div class="mb-2 form-check">
                            <input type="checkbox" name="is_internal_note" class="form-check-input" id="is_internal_note">
                            <label class="form-check-label" for="is_internal_note">Nota interna (não visível ao denunciante)</label>
                        </div>
                        <div class="mb-2 canal-attachments-field">
                            <input type="file" name="attachments[]" id="wb-reply-attachments" class="canal-attachments-input" multiple
                                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.ogg,.opus,.mp4,.doc,.docx"
                                style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;"
                                tabindex="-1" aria-hidden="true">
                            <button type="button" class="btn btn-outline-primary btn-sm canal-attachments-add">
                                <i class="fas fa-paperclip me-1"></i>Adicionar arquivos
                            </button>
                            <div class="form-text small mt-1">Máx. <?= htmlspecialchars(\App\adms\Models\Services\WhistleblowingUploadService::maxFileSizeLabel(), ENT_QUOTES, 'UTF-8') ?> — PDF, imagem, MP3/WAV/OGG ou MP4.</div>
                            <div class="canal-attachment-list mt-2"></div>
                            <div class="canal-attachment-feedback alert alert-danger py-2 small mt-2 d-none" role="alert"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Enviar</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-12">
            <?php if ($canStatus): ?>
            <div class="card border-light shadow-sm mb-3">
                <div class="card-header">Gestão</div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>update-denuncia-status/<?php echo (int)($report['id'] ?? 0); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($this->data['csrf_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-2">
                            <label class="form-label small">Status</label>
                            <select name="status" id="wb-status-select" class="form-select form-select-sm">
                                <?php foreach ($this->data['statuses'] as $st): ?>
                                    <option value="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedStatus === $st) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Para concluir a apuração, selecione <strong>Encerrada</strong>. Os campos de resultado e motivo aparecerão abaixo.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Risco</label>
                            <select name="risk_level" class="form-select form-select-sm">
                                <?php foreach ($this->data['risk_levels'] as $risk): ?>
                                    <option value="<?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedRisk === $risk) ? 'selected' : ''; ?>><?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Responsável</label>
                            <select name="assigned_user_id" class="form-select form-select-sm">
                                <option value="">—</option>
                                <?php foreach ($this->data['users'] as $u): ?>
                                    <option value="<?php echo (int)$u['id']; ?>" <?php echo ($selectedAssignedUser === (int)$u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$u['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Observação (linha do tempo)</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars((string)($statusForm['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div id="wb-closure-fields" class="alert alert-warning border-warning mb-3 <?= $selectedStatus === 'Encerrada' ? '' : 'd-none' ?>">
                            <h6 class="alert-heading mb-2"><i class="fas fa-check-circle me-1"></i>Conclusão da apuração</h6>
                            <p class="small mb-2">Escolha o resultado e explique o motivo. Essas informações serão apresentadas ao denunciante.</p>
                            <label class="form-label small fw-semibold">Resultado do encerramento <span class="text-danger">*</span></label>
                            <select name="closure_outcome" class="form-select form-select-sm mb-2">
                                <option value="">Selecione...</option>
                                <?php foreach (($this->data['closure_outcomes'] ?? []) as $outcome): ?>
                                    <option value="<?php echo htmlspecialchars($outcome, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo ($selectedClosureOutcome === $outcome) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($outcome, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label class="form-label small fw-semibold">Motivo do encerramento <span class="text-danger">*</span></label>
                            <textarea name="closure_reason" class="form-control form-control-sm" rows="3" placeholder="Resumo claro da conclusão da apuração..."><?php echo htmlspecialchars($selectedClosureReason, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <?php if (!empty($report['closure_outcome'])): ?>
                        <div class="alert alert-secondary py-2 small mb-2">
                            <strong>Encerramento:</strong> <?php echo htmlspecialchars((string)$report['closure_outcome'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($report['closure_reason'])): ?>
                                <div class="mt-1"><?php echo nl2br(htmlspecialchars((string)$report['closure_reason'], ENT_QUOTES, 'UTF-8')); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-warning btn-sm w-100">
                            <i class="fas fa-save me-1"></i><span id="wb-status-submit-label"><?= $selectedStatus === 'Encerrada' ? 'Confirmar encerramento' : 'Atualizar gestão' ?></span>
                        </button>
                    </form>
                    <script>
                    (function () {
                        var statusSelect = document.querySelector('select[name="status"]');
                        var closureBox = document.getElementById('wb-closure-fields');
                        var closureOutcome = document.querySelector('select[name="closure_outcome"]');
                        var closureReason = document.querySelector('textarea[name="closure_reason"]');
                        var submitLabel = document.getElementById('wb-status-submit-label');
                        function toggleClosure() {
                            if (!statusSelect || !closureBox) return;
                            var closing = statusSelect.value === 'Encerrada';
                            closureBox.classList.toggle('d-none', !closing);
                            if (closureOutcome) closureOutcome.required = closing;
                            if (closureReason) closureReason.required = closing;
                            if (submitLabel) submitLabel.textContent = closing ? 'Confirmar encerramento' : 'Atualizar gestão';
                        }
                        statusSelect?.addEventListener('change', toggleClosure);
                        toggleClosure();
                    })();
                    </script>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['attachments'])): ?>
            <div class="card border-light shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Anexos</span>
                    <span class="badge bg-light text-dark border small fw-normal">Download somente via módulo</span>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($this->data['attachments'] as $att): ?>
                        <?php
                        $storedName = strtolower((string) ($att['stored_name'] ?? ''));
                        $isEncrypted = str_ends_with($storedName, '.enc');
                        $sizeBytes = (int) ($att['size_bytes'] ?? 0);
                        $sizeLabel = $sizeBytes >= 1048576
                            ? round($sizeBytes / 1048576, 1) . ' MB'
                            : ($sizeBytes >= 1024 ? round($sizeBytes / 1024, 1) . ' KB' : $sizeBytes . ' B');
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2 small">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-semibold text-break"><?php echo htmlspecialchars((string)($att['original_name'] ?? 'arquivo'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-muted">
                                    <?php if ($isEncrypted): ?>
                                        <span class="badge bg-success">Cifrado em disco</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Legado (sem cifra)</span>
                                    <?php endif; ?>
                                    <span class="ms-1"><?php echo htmlspecialchars($sizeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (!empty($att['created_at'])): ?>
                                        — <?php echo date('d/m/Y H:i', strtotime((string) $att['created_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/download-attachment/<?php echo (int)$att['id']; ?>"
                               class="btn btn-outline-secondary btn-sm py-0 flex-shrink-0"
                               title="Download autorizado — o sistema descriptografa automaticamente se o arquivo estiver cifrado">
                                <i class="fas fa-download"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer small text-muted">
                    Arquivos cifrados usam extensão <code>.enc</code> no servidor e não podem ser abertos por URL direta.
                    Cada download é registrado em <strong>Auditoria de acesso</strong>.
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card border-light shadow-sm h-100">
                <div class="card-header"><i class="fas fa-stream me-1"></i>Linha do tempo</div>
                <ul class="list-group list-group-flush small" style="max-height: 360px; overflow-y: auto;">
                    <?php foreach ($this->data['status_log'] ?? [] as $log): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars((string)($log['to_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="text-muted"><?php echo date('d/m/Y H:i', strtotime((string)($log['created_at'] ?? 'now'))); ?>
                                <?php if (!empty($log['user_name'])): ?> — <?php echo htmlspecialchars((string)$log['user_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            </div>
                            <?php if (!empty($log['notes'])): ?><div class="mt-1"><?php echo htmlspecialchars((string)$log['notes'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-light shadow-sm h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="fas fa-user-shield me-1"></i>Auditoria de acesso interno</span>
                    <?php if (in_array('WhistleblowingExportAccessLog', $this->data['buttonPermission'] ?? [])): ?>
                    <div class="btn-group btn-group-sm">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>whistleblowing-export-access-log/<?php echo (int)($report['id'] ?? 0); ?>?format=excel"
                           class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel me-1"></i>Excel</a>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>whistleblowing-export-access-log/<?php echo (int)($report['id'] ?? 0); ?>?format=pdf"
                           class="btn btn-outline-danger btn-sm"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="list-group list-group-flush small" style="max-height: 420px; overflow-y: auto;">
                    <?php
                    $accessActionLabels = [
                        'view' => 'visualização',
                        'download_attachment' => 'download de anexo',
                        'status_change' => 'alteração de status',
                        'reply' => 'resposta ao denunciante',
                        'internal_note' => 'nota interna',
                        'export_audit_pdf' => 'exportação da auditoria (PDF)',
                        'export_audit_excel' => 'exportação da auditoria (Excel)',
                    ];
                    ?>
                    <?php foreach ($this->data['access_log'] ?? [] as $al): ?>
                        <?php $accessAction = (string)($al['action'] ?? ''); ?>
                        <div class="list-group-item">
                            <div class="row g-2 align-items-start">
                                <div class="col-12 col-md-4">
                                    <strong><?php echo htmlspecialchars((string)($al['user_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="text-muted"><?php echo htmlspecialchars($accessActionLabels[$accessAction] ?? $accessAction, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <span class="d-md-none fw-semibold">Data: </span>
                                    <span class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime((string)($al['created_at'] ?? 'now'))); ?></span>
                                </div>
                                <div class="col-12 col-md-5">
                                    <?php if (!empty($al['ip_address'])): ?>
                                        <div><span class="fw-semibold">IP interno:</span> <?php echo htmlspecialchars((string)$al['ip_address'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($al['user_agent'])): ?>
                                        <div class="text-muted text-break mt-1">
                                            <span class="fw-semibold text-dark">Dispositivo:</span>
                                            <?php echo htmlspecialchars((string)$al['user_agent'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer small text-muted">
                    Registra somente acessos e ações de usuários internos autenticados; nunca o IP do denunciante.
                </div>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__) . '/public/_attachment_validation.php'; ?>
