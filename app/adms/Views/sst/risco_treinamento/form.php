<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_risco_treinamento_form');
$action = $isEdit ? 'sst-update-risco-treinamento/' . (int)$item['id'] : 'sst-create-risco-treinamento';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <h2 class="mt-3"><i class="fas fa-link me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> Treinamento por Risco</h2>
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Risco *</label>
                        <select name="adms_sst_risco_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['riscos'] ?? [] as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= ((int)($item['adms_sst_risco_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Treinamento *</label>
                        <select name="adms_sst_treinamento_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['treinamentos'] ?? [] as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= ((int)($item['adms_sst_treinamento_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Validade (meses)</label>
                        <input type="number" name="validade_meses" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['validade_meses'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="obrigatorio" class="form-check-input" value="1" <?= !isset($item['obrigatorio']) || !empty($item['obrigatorio']) ? 'checked' : '' ?>>
                            <label class="form-check-label">Obrigatório</label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-riscos" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
