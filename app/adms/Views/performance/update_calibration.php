<?php
use App\adms\Helpers\CSRFHelper;
$cal = $this->data['calibration'] ?? [];
$isLocked = ($cal['status'] ?? '') === 'locked';
$cycleId = (int) ($cal['performance_cycle_id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Calibração</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-calibrations" class="text-decoration-none">Calibração</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-balance-scale me-2"></i><?= htmlspecialchars($cal['cycle_name'] ?? 'Calibração') ?></span>
            <?php if (in_array('NineBoxMatrix', $this->data['buttonPermission'] ?? [], true)) { ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>nine-box-matrix?performance_cycle_id=<?= $cycleId ?>" class="btn btn-sm btn-outline-primary">
                    Nine Box do ciclo
                </a>
            <?php } ?>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if ($isLocked): ?>
                <div class="alert alert-warning">Sessão travada — somente leitura. As avaliações do ciclo também ficam bloqueadas para edição.</div>
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-calibration/<?= (int) $cal['id'] ?>" class="btn btn-secondary">Voltar</a>
            <?php else: ?>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_performance_calibration'); ?>">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= ($cal['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                            <option value="open" <?= ($cal['status'] ?? '') === 'open' ? 'selected' : '' ?>>Aberta</option>
                            <option value="locked" <?= ($cal['status'] ?? '') === 'locked' ? 'selected' : '' ?>>Travada</option>
                        </select>
                        <small class="text-muted">Travada encerra a sessão e bloqueia edição das avaliações do ciclo.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notas da sessão (ata)</label>
                        <textarea name="session_notes" class="form-control" rows="4"><?= htmlspecialchars((string) ($cal['session_notes'] ?? '')) ?></textarea>
                    </div>

                    <div class="col-12">
                        <h5 class="mt-2">Notas das avaliações</h5>
                        <p class="text-muted small mb-2">
                            Ajuste desempenho e potencial (0–10). A primeira alteração guarda o valor pré-calibração para comparação.
                        </p>
                        <?php if (empty($this->data['reviews'])): ?>
                            <div class="alert alert-info mb-0">Nenhuma avaliação vinculada a este ciclo. Gere avaliações no ciclo antes de calibrar.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Colaborador</th>
                                            <th>Tipo</th>
                                            <th>Pré-calibração</th>
                                            <th style="width:7rem">Desempenho</th>
                                            <th style="width:7rem">Potencial</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->data['reviews'] as $review):
                                            if (($review['status'] ?? '') === 'cancelled') {
                                                continue;
                                            }
                                            $rid = (int) $review['id'];
                                            $preO = $review['overall_score_pre_calibration'] ?? null;
                                            $preP = $review['potential_score_pre_calibration'] ?? null;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($review['employee_name'] ?? '') ?></td>
                                                <td><?= htmlspecialchars((string) ($review['review_type'] ?? '')) ?></td>
                                                <td class="small text-muted">
                                                    <?php if ($preO !== null || $preP !== null): ?>
                                                        D: <?= $preO !== null ? htmlspecialchars((string) $preO) : '—' ?>
                                                        /
                                                        P: <?= $preP !== null ? htmlspecialchars((string) $preP) : '—' ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="10" class="form-control form-control-sm"
                                                           name="scores[<?= $rid ?>][overall_score]"
                                                           value="<?= $review['overall_score'] !== null ? htmlspecialchars((string) $review['overall_score']) : '' ?>">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="10" class="form-control form-control-sm"
                                                           name="scores[<?= $rid ?>][potential_score]"
                                                           value="<?= $review['potential_score'] !== null ? htmlspecialchars((string) $review['potential_score']) : '' ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-success">Salvar</button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-calibration/<?= (int) $cal['id'] ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
