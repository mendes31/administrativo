<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Marcar Cargo Crítico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-critical-positions" class="text-decoration-none">Sucessão</a></li>
            <li class="breadcrumb-item">Novo</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">Novo cargo crítico</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_critical_position'); ?>">
                <div class="col-md-6">
                    <label class="form-label">Cargo <span class="text-danger">*</span></label>
                    <select name="position_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($this->data['positions'] ?? [] as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= ((int) ($form['position_id'] ?? 0) === (int) $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Risco</label>
                    <select name="risk_level" class="form-select">
                        <option value="high" <?= ($form['risk_level'] ?? '') === 'high' ? 'selected' : '' ?>>Alto</option>
                        <option value="medium" <?= ($form['risk_level'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Médio</option>
                        <option value="low" <?= ($form['risk_level'] ?? '') === 'low' ? 'selected' : '' ?>>Baixo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" selected>Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($form['notes'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-critical-positions" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
