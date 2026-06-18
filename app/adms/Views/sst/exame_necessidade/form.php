<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_exame_necessidade_form');
$action = $isEdit ? 'sst-update-exame-necessidade/' . (int)$item['id'] : 'sst-create-exame-necessidade';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-clipboard-list me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('Necessidade de Exame') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-exame-necessidade"><?= htmlspecialchars('Necessidades de Exame') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
<label class="form-label" for="adms_sst_risco_id">Risco</label>
<select name="adms_sst_risco_id" id="adms_sst_risco_id" class="form-select" ><option value="">Selecione...</option>
<?php foreach ($this->data['riscos'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_risco_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_sst_exame_id">Exame *</label>
<select name="adms_sst_exame_id" id="adms_sst_exame_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['exames'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_exame_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="periodicidade_meses">Periodicidade (meses)</label>
<input type="number" name="periodicidade_meses" id="periodicidade_meses" class="form-control" value="<?= htmlspecialchars($item['periodicidade_meses'] ?? '') ?>" >
</div>
<?php $selected = $item['categoria_aso'] ?? ''; include './app/adms/Views/sst/partials/field_categoria_aso.php'; ?>
<div class="col-md-6 mb-3">
<label class="form-label" for="obrigatorio">Obrigatório</label>
<div class="form-check"><input type="checkbox" name="obrigatorio" id="obrigatorio" class="form-check-input" value="1" <?= !empty($item['obrigatorio']) ? 'checked' : '' ?>><label class="form-check-label" for="obrigatorio">Obrigatório</label></div>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="observacoes">Observações</label>
<textarea name="observacoes" id="observacoes" class="form-control" rows="3" ><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
</div>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-exame-necessidade" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>