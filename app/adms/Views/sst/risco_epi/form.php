<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_risco_epi_form');
$action = $isEdit ? 'sst-update-risco-epi/' . (int)$item['id'] : 'sst-create-risco-epi';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-link me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> EPI por Risco</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-risco-epi">EPIs por Risco</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="adms_sst_risco_id">Risco *</label>
                        <select name="adms_sst_risco_id" id="adms_sst_risco_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['riscos'] ?? [] as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= ((int)($item['adms_sst_risco_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="adms_sst_epi_id">EPI *</label>
                        <select name="adms_sst_epi_id" id="adms_sst_epi_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['epis'] ?? [] as $ep): ?>
                                <option value="<?= (int)$ep['id'] ?>" <?= ((int)($item['adms_sst_epi_id'] ?? 0) === (int)$ep['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ep['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="obrigatorio">Obrigatório</label>
                        <div class="form-check">
                            <input type="checkbox" name="obrigatorio" id="obrigatorio" class="form-check-input" value="1" <?= !isset($item['obrigatorio']) || !empty($item['obrigatorio']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="obrigatorio">Obrigatório</label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-risco-epi" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
