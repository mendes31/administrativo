<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_afastamentos_form');
$action = $isEdit ? 'sst-update-afastamento/' . (int)$item['id'] : 'sst-create-afastamento';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-procedures me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('Afastamento') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-afastamentos"><?= htmlspecialchars('Afastamentos') ?></a></li>
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
<label class="form-label" for="adms_sst_cid_id">CID</label>
<select name="adms_sst_cid_id" id="adms_sst_cid_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['cids'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_cid_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars(($x['codigo'] ?? '') . ' - ' . ($x['descricao'] ?? '')) ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_sst_medico_id">Médico</label>
<select name="adms_sst_medico_id" id="adms_sst_medico_id" class="form-select"><option value="">Selecione...</option>
<?php foreach ($this->data['medicos'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_medico_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="tipo">Tipo</label>
<select name="tipo" id="tipo" class="form-select" >
<option value="Doença" <?= (($item['tipo'] ?? '') === 'Doença') ? 'selected' : '' ?>>Doença</option>
<option value="Acidente de trabalho" <?= (($item['tipo'] ?? '') === 'Acidente de trabalho') ? 'selected' : '' ?>>Acidente de trabalho</option>
<option value="Licença" <?= (($item['tipo'] ?? '') === 'Licença') ? 'selected' : '' ?>>Licença</option>
<option value="Maternidade" <?= (($item['tipo'] ?? '') === 'Maternidade') ? 'selected' : '' ?>>Maternidade</option>
<option value="Outro" <?= (($item['tipo'] ?? '') === 'Outro') ? 'selected' : '' ?>>Outro</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_inicio">Data início *</label>
<input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($item['data_inicio'] ?? '') ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_fim">Data fim</label>
<input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($item['data_fim'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="dias_afastamento">Dias</label>
<input type="number" name="dias_afastamento" id="dias_afastamento" class="form-control" value="<?= htmlspecialchars($item['dias_afastamento'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_retorno">Data retorno</label>
<input type="date" name="data_retorno" id="data_retorno" class="form-control" value="<?= htmlspecialchars($item['data_retorno'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="status">Status</label>
<select name="status" id="status" class="form-select" >
<option value="Ativo" <?= (($item['status'] ?? '') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
<option value="Encerrado" <?= (($item['status'] ?? '') === 'Encerrado') ? 'selected' : '' ?>>Encerrado</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="observacoes">Observações</label>
<textarea name="observacoes" id="observacoes" class="form-control" rows="3" ><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
</div>
<?php include './app/adms/Views/sst/partials/form_anexos.php'; ?>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-afastamentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>