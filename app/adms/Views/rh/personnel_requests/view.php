<?php
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

    <div class="card border-light shadow mb-3">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <dl class="row mb-0">
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><span class="badge bg-secondary"><?= htmlspecialchars($status) ?></span></dd>
                <dt class="col-sm-3">Solicitante</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($r['requester_nome'] ?? '-') ?></dd>
                <dt class="col-sm-3">Área / Cargo</dt>
                <dd class="col-sm-9">
                    <?= htmlspecialchars($r['area_nome'] ?? '-') ?> /
                    <?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string) ($r['cargo_nome'] ?? '')) ?: '-') ?>
                </dd>
                <dt class="col-sm-3">Quantidade</dt>
                <dd class="col-sm-9"><?= (int) ($r['quantidade'] ?? 1) ?> (<?= htmlspecialchars($r['tipo_contrato'] ?? '') ?>)</dd>
                <dt class="col-sm-3">Motivo</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($r['motivo_tipo'] ?? '') ?></dd>
                <dt class="col-sm-3">Data desejada</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($r['data_desejada'] ?? '-') ?></dd>
                <dt class="col-sm-3">Justificativa</dt>
                <dd class="col-sm-9"><?= nl2br(htmlspecialchars($r['justificativa'] ?? '')) ?></dd>
                <?php if (!empty($r['approved_by_nome'])): ?>
                    <dt class="col-sm-3">Decisão por</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($r['approved_by_nome']) ?> em <?= FormatHelper::formatDateTime($r['approved_at'] ?? null) ?></dd>
                <?php endif; ?>
                <?php if (!empty($r['rejection_reason'])): ?>
                    <dt class="col-sm-3">Motivo rejeição</dt>
                    <dd class="col-sm-9"><?= nl2br(htmlspecialchars($r['rejection_reason'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($r['converted_vaga_id'])): ?>
                    <dt class="col-sm-3">Vaga gerada</dt>
                    <dd class="col-sm-9">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= (int) $r['converted_vaga_id'] ?>">
                            #<?= (int) $r['converted_vaga_id'] ?>
                        </a>
                    </dd>
                <?php endif; ?>
            </dl>
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
