<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$returnRiscoId = (int) ($this->data['return_risco_id'] ?? $item['adms_sst_risco_id'] ?? 0);
$cancelUrl = $returnRiscoId > 0
    ? SstRiscoNavigationHelper::viewUrl($returnRiscoId, 'cargos')
    : ($_ENV['URL_ADM'] . 'sst-list-riscos');
$csrfToken = CSRFHelper::generateCSRFToken('sst_riscos_cargo_form');
$action = $isEdit ? 'sst-update-risco-cargo/' . (int)$item['id'] : 'sst-create-risco-cargo';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-shield-virus me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('Risco por Cargo') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
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
<label class="form-label" for="adms_position_id">Cargo</label>
<select name="adms_position_id" id="adms_position_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['positions'] ?? [] as $p): ?><option value="<?= (int)$p['id'] ?>" <?= ((int)($item['adms_position_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_department_id">Departamento</label>
<select name="adms_department_id" id="adms_department_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['departments'] ?? [] as $d): ?><option value="<?= (int)$d['id'] ?>" <?= ((int)($item['adms_department_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_sst_risco_id">Risco *</label>
<select name="adms_sst_risco_id" id="adms_sst_risco_id" class="form-select" required><option value="">Selecione...</option>
<?php foreach ($this->data['riscos'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_risco_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="nivel">Nível</label>
<select name="nivel" id="nivel" class="form-select" >
<option value="Baixo" <?= (($item['nivel'] ?? '') === 'Baixo') ? 'selected' : '' ?>>Baixo</option>
<option value="Médio" <?= (($item['nivel'] ?? '') === 'Médio') ? 'selected' : '' ?>>Médio</option>
<option value="Alto" <?= (($item['nivel'] ?? '') === 'Alto') ? 'selected' : '' ?>>Alto</option>
<option value="Crítico" <?= (($item['nivel'] ?? '') === 'Crítico') ? 'selected' : '' ?>>Crítico</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="observacoes">Observações</label>
<textarea name="observacoes" id="observacoes" class="form-control" rows="3" ><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
</div>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= htmlspecialchars($cancelUrl) ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>