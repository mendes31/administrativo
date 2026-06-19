<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$returnRiscoId = (int) ($this->data['return_risco_id'] ?? $item['adms_sst_risco_id'] ?? 0);
$cancelUrl = $returnRiscoId > 0
    ? SstRiscoNavigationHelper::viewUrl($returnRiscoId, 'exames')
    : ($_ENV['URL_ADM'] . 'sst-list-riscos');
$csrfToken = CSRFHelper::generateCSRFToken('sst_risco_exame_form');
$action = $isEdit ? 'sst-update-risco-exame/' . (int)$item['id'] : 'sst-create-risco-exame';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-link me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> Exame por Risco</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-riscos">Riscos</a></li>
            <?php if ($returnRiscoId > 0): ?>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars(SstRiscoNavigationHelper::viewUrl($returnRiscoId)) ?>">Risco</a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?php if ($returnRiscoId > 0): ?>
                <input type="hidden" name="return_risco_id" value="<?= $returnRiscoId ?>">
                <?php endif; ?>
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
                        <label class="form-label" for="adms_sst_exame_id">Exame complementar *</label>
                        <select name="adms_sst_exame_id" id="adms_sst_exame_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['exames'] ?? [] as $ex): ?>
                                <option value="<?= (int)$ex['id'] ?>" <?= ((int)($item['adms_sst_exame_id'] ?? 0) === (int)$ex['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ex['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php
                    $selectedCategorias = $this->data['categorias_aso_selecionadas'] ?? [];
                    include './app/adms/Views/sst/partials/field_categorias_aso_checkboxes.php';
                    ?>
                    <?php if ($isEdit): ?>
                    <input type="hidden" name="edit_origem_risco_id" value="<?= (int) ($item['adms_sst_risco_id'] ?? 0) ?>">
                    <input type="hidden" name="edit_origem_exame_id" value="<?= (int) ($item['adms_sst_exame_id'] ?? 0) ?>">
                    <?php endif; ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="periodicidade_meses">Periodicidade (meses)</label>
                        <input type="number" name="periodicidade_meses" id="periodicidade_meses" class="form-control" min="1"
                               value="<?= htmlspecialchars((string)($item['periodicidade_meses'] ?? '')) ?>">
                        <div class="form-text">Opcional: sobrescreve a periodicidade padrão do exame para este risco. Vazio = usa o catálogo.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="obrigatorio">Exigência na matriz</label>
                        <div class="form-check">
                            <input type="checkbox" name="obrigatorio" id="obrigatorio" class="form-check-input" value="1"
                                <?= empty($item['id']) || !empty($item['obrigatorio']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="obrigatorio">Obrigatório</label>
                        </div>
                        <div class="form-text">Desmarcado = exame <strong>recomendado</strong> (opcional no encaminhamento e no pacote do ASO).</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= htmlspecialchars($cancelUrl) ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
