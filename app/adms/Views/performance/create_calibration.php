<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nova Calibração</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-calibrations" class="text-decoration-none">Calibração</a></li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header"><span><i class="fas fa-balance-scale me-2"></i>Abrir sessão</span></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (empty($this->data['cycles'])): ?>
                <div class="alert alert-warning">
                    Não há ciclos disponíveis (rascunho/aberto sem calibração).
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-performance-cycle">Criar ciclo</a>
                </div>
            <?php else: ?>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_performance_calibration'); ?>">
                    <div class="col-md-8">
                        <label class="form-label">Ciclo <span class="text-danger">*</span></label>
                        <select name="performance_cycle_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['cycles'] as $cycle): ?>
                                <option value="<?= (int) $cycle['id'] ?>" <?= ((int) ($form['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cycle['name']) ?> (<?= htmlspecialchars($cycle['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status inicial</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= ($form['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                            <option value="open" <?= ($form['status'] ?? '') === 'open' ? 'selected' : '' ?>>Aberta</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notas da sessão</label>
                        <textarea name="session_notes" class="form-control" rows="4"><?= htmlspecialchars((string) ($form['session_notes'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Salvar</button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-calibrations" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
