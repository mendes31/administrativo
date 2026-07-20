<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Cargo Crítico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-critical-positions" class="text-decoration-none">Sucessão</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header"><?= htmlspecialchars($item['position_name'] ?? '') ?></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_critical_position'); ?>">
                <div class="col-md-4">
                    <label class="form-label">Risco</label>
                    <select name="risk_level" class="form-select">
                        <?php foreach (['high' => 'Alto', 'medium' => 'Médio', 'low' => 'Baixo'] as $code => $label): ?>
                            <option value="<?= $code ?>" <?= ($item['risk_level'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($item['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= ($item['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-critical-position/<?= (int) ($item['id'] ?? 0) ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
