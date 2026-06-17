<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_asos_form');
$action = $isEdit ? 'sst-update-aso/' . (int)$item['id'] : 'sst-create-aso';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-file-medical me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('ASO') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos"><?= htmlspecialchars('ASOs') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
<label class="form-label" for="adms_user_id">Colaborador *</label>
<select name="adms_user_id" id="adms_user_id" class="form-select" required><option value="">Selecione...</option>
<?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((int)($item['adms_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_sst_exame_id">Exame</label>
<select name="adms_sst_exame_id" id="adms_sst_exame_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['exames'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_exame_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_sst_medico_id">Médico</label>
<select name="adms_sst_medico_id" id="adms_sst_medico_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['medicos'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_medico_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="tipo">Tipo *</label>
<select name="tipo" id="tipo" class="form-select" required>
<option value="Admissional" <?= (($item['tipo'] ?? '') === 'Admissional') ? 'selected' : '' ?>>Admissional</option>
<option value="Periódico" <?= (($item['tipo'] ?? '') === 'Periódico') ? 'selected' : '' ?>>Periódico</option>
<option value="Mudança de função" <?= (($item['tipo'] ?? '') === 'Mudança de função') ? 'selected' : '' ?>>Mudança de função</option>
<option value="Retorno ao trabalho" <?= (($item['tipo'] ?? '') === 'Retorno ao trabalho') ? 'selected' : '' ?>>Retorno ao trabalho</option>
<option value="Demissional" <?= (($item['tipo'] ?? '') === 'Demissional') ? 'selected' : '' ?>>Demissional</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_realizacao">Data realização *</label>
<input type="date" name="data_realizacao" id="data_realizacao" class="form-control" value="<?= htmlspecialchars($item['data_realizacao'] ?? '') ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_validade">Validade</label>
<input type="date" name="data_validade" id="data_validade" class="form-control" value="<?= htmlspecialchars($item['data_validade'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="resultado">Resultado</label>
<select name="resultado" id="resultado" class="form-select" >
<option value="Apto" <?= (($item['resultado'] ?? '') === 'Apto') ? 'selected' : '' ?>>Apto</option>
<option value="Inapto" <?= (($item['resultado'] ?? '') === 'Inapto') ? 'selected' : '' ?>>Inapto</option>
<option value="Apto com restrição" <?= (($item['resultado'] ?? '') === 'Apto com restrição') ? 'selected' : '' ?>>Apto com restrição</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="restricoes">Restrições</label>
<textarea name="restricoes" id="restricoes" class="form-control" rows="3" ><?= htmlspecialchars($item['restricoes'] ?? '') ?></textarea>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="clinica">Clínica</label>
<input type="text" name="clinica" id="clinica" class="form-control" value="<?= htmlspecialchars($item['clinica'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="observacoes">Observações</label>
<textarea name="observacoes" id="observacoes" class="form-control" rows="3" ><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
</div>
<?php include './app/adms/Views/sst/partials/form_anexos.php'; ?>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>