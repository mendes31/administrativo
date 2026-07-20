<?php
use App\adms\Helpers\CSRFHelper;
$cal = $this->data['calibration'] ?? [];
$isLocked = ($cal['status'] ?? '') === 'locked';
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
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if ($isLocked): ?>
                <div class="alert alert-warning">Sessão travada — somente leitura.</div>
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
                        <small class="text-muted">Travada encerra a sessão (não reabre neste incremento).</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notas da sessão</label>
                        <textarea name="session_notes" class="form-control" rows="5"><?= htmlspecialchars((string) ($cal['session_notes'] ?? '')) ?></textarea>
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
