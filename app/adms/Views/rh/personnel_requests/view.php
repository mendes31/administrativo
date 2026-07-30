<?php
/** Detalhe da requisição de pessoal (aprovação / conversão). */
use App\adms\Helpers\FormatHelper;
$r = $this->data['request'] ?? [];
$status = $r['status'] ?? '';
$actorId = (int) ($_SESSION['user_id'] ?? 0);
$canDecide = $status === 'pending_approval' && (int) ($r['requester_id'] ?? 0) !== $actorId;
$canConvert = $status === 'approved';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Requisição #<?= (int) ($r['id'] ?? 0) ?></h2>
        <a class="btn btn-outline-secondary btn-sm ms-auto" href="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm mb-3 overflow-hidden">
        <div class="card-body p-3 p-md-4" style="background: linear-gradient(135deg, #ecfdf5 0%, #f8fafc 100%);">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="d-flex flex-wrap gap-1 mb-3">
                <span class="badge rounded-pill text-bg-light border">Req. #<?= (int) ($r['id'] ?? 0) ?></span>
                <span class="badge rounded-pill bg-secondary-subtle text-secondary border"><?= htmlspecialchars($status) ?></span>
            </div>
            <div class="row g-2">
                <div class="col-6 col-md-4">
                    <div class="bg-white border rounded-3 p-2 h-100">
                        <div class="small text-muted">Solicitante</div>
                        <div class="fw-semibold small text-break"><?= htmlspecialchars($r['requester_nome'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white border rounded-3 p-2 h-100">
                        <div class="small text-muted">Área / Cargo</div>
                        <div class="fw-semibold small text-break">
                            <?= htmlspecialchars($r['area_nome'] ?? '-') ?> /
                            <?= htmlspecialchars((string) ($r['cargo_nome'] ?? '') ?: '-') ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white border rounded-3 p-2 h-100">
                        <div class="small text-muted">Quantidade</div>
                        <div class="fw-semibold small"><?= (int) ($r['quantidade'] ?? 1) ?> (<?= htmlspecialchars($r['tipo_contrato'] ?? '') ?>)</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white border rounded-3 p-2 h-100">
                        <div class="small text-muted">Motivo</div>
                        <div class="fw-semibold small text-break"><?= htmlspecialchars($r['motivo_tipo'] ?? '') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="bg-white border rounded-3 p-2 h-100">
                        <div class="small text-muted">Data desejada</div>
                        <div class="fw-semibold small"><?= htmlspecialchars($r['data_desejada'] ?? '-') ?></div>
                    </div>
                </div>
                <?php if (!empty($r['converted_vaga_id'])): ?>
                    <div class="col-6 col-md-4">
                        <div class="bg-white border rounded-3 p-2 h-100">
                            <div class="small text-muted">Vaga gerada</div>
                            <div class="fw-semibold small">
                                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= (int) $r['converted_vaga_id'] ?>">
                                    #<?= (int) $r['converted_vaga_id'] ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($r['justificativa'])): ?>
                <div class="mt-3 bg-white border rounded-3 p-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">Justificativa</div>
                    <div class="small text-break"><?= nl2br(htmlspecialchars($r['justificativa'] ?? '')) ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($r['approved_by_nome'])): ?>
                <p class="small text-muted mt-3 mb-0">
                    Decisão por <?= htmlspecialchars($r['approved_by_nome']) ?>
                    em <?= FormatHelper::formatDateTime($r['approved_at'] ?? null) ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($r['rejection_reason'])): ?>
                <div class="alert alert-danger small mt-3 mb-0">
                    <?= nl2br(htmlspecialchars($r['rejection_reason'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canDecide): ?>
        <div class="card border-light shadow mb-3">
            <div class="card-header">Aprovação</div>
            <div class="card-body d-flex flex-wrap gap-3">
                <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests-approve/<?= (int) $r['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->data['csrf_approve'] ?? '') ?>">
                    <button type="submit" class="btn btn-success">Aprovar</button>
                </form>
                <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests-reject/<?= (int) $r['id'] ?>" class="flex-grow-1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->data['csrf_reject'] ?? '') ?>">
                    <div class="input-group">
                        <input type="text" name="rejection_reason" class="form-control" placeholder="Motivo da rejeição" required>
                        <button type="submit" class="btn btn-danger">Rejeitar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canConvert): ?>
        <div class="card border-light shadow mb-3">
            <div class="card-header">Converter em vaga</div>
            <div class="card-body">
                <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests-convert/<?= (int) $r['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->data['csrf_convert'] ?? '') ?>">
                    <div class="mb-2">
                        <label class="form-label">Título da vaga *</label>
                        <input type="text" name="titulo" class="form-control" required
                               value="<?= htmlspecialchars(($r['cargo_nome'] ?? 'Vaga') . ' — requisição #' . (int) $r['id']) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($r['justificativa'] ?? '') ?></textarea>
                    </div>
                    <p class="small text-muted">A vaga será criada como <strong>pausada</strong> para você completar requisitos e abrir o pipeline.</p>
                    <button type="submit" class="btn btn-primary">Criar vaga</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
