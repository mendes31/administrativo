<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_acidentes_form');
$action = $isEdit ? 'sst-update-acidente/' . (int)$item['id'] : 'sst-create-acidente';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-ambulance me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('Acidente/Incidente') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-acidentes"><?= htmlspecialchars('Acidentes e Incidentes') ?></a></li>
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
<label class="form-label" for="tipo">Tipo</label>
<select name="tipo" id="tipo" class="form-select" >
<option value="Acidente" <?= (($item['tipo'] ?? '') === 'Acidente') ? 'selected' : '' ?>>Acidente</option>
<option value="Incidente" <?= (($item['tipo'] ?? '') === 'Incidente') ? 'selected' : '' ?>>Incidente</option>
<option value="Quase acidente" <?= (($item['tipo'] ?? '') === 'Quase acidente') ? 'selected' : '' ?>>Quase acidente</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_ocorrencia">Data/hora *</label>
<?php $dt = $item['data_ocorrencia'] ?? ''; $dtVal = $dt ? date('Y-m-d\TH:i', strtotime($dt)) : ''; ?>
<input type="datetime-local" name="data_ocorrencia" id="data_ocorrencia" class="form-control" value="<?= $dtVal ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="local">Local</label>
<input type="text" name="local" id="local" class="form-control" value="<?= htmlspecialchars($item['local'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="descricao">Descrição *</label>
<textarea name="descricao" id="descricao" class="form-control" rows="3" required><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="cat_numero">Nº CAT</label>
<input type="text" name="cat_numero" id="cat_numero" class="form-control" value="<?= htmlspecialchars($item['cat_numero'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="cat_data">Data CAT</label>
<input type="date" name="cat_data" id="cat_data" class="form-control" value="<?= htmlspecialchars($item['cat_data'] ?? '') ?>" >
</div>
<div class="col-md-12 mb-3">
<label class="form-label" for="investigacao">Investigação</label>
<textarea name="investigacao" id="investigacao" class="form-control" rows="4" placeholder="Causas, fatores contribuintes, testemunhas, medidas imediatas..."><?= htmlspecialchars($item['investigacao'] ?? '') ?></textarea>
<small class="text-muted">Preencha ao alterar o status para "Em investigação". Os planos de ação estruturados são cadastrados na tela de visualização do acidente.</small>
</div>
<div class="col-md-6 mb-3 d-none">
<label class="form-label" for="plano_acao">Resumo plano (legado)</label>
<textarea name="plano_acao" id="plano_acao" class="form-control" rows="2" ><?= htmlspecialchars($item['plano_acao'] ?? '') ?></textarea>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="status">Status</label>
<select name="status" id="status" class="form-select" >
<option value="Aberto" <?= (($item['status'] ?? '') === 'Aberto') ? 'selected' : '' ?>>Aberto</option>
<option value="Em investigação" <?= (($item['status'] ?? '') === 'Em investigação') ? 'selected' : '' ?>>Em investigação</option>
<option value="Encerrado" <?= (($item['status'] ?? '') === 'Encerrado') ? 'selected' : '' ?>>Encerrado</option>
</select>
</div>
<?php include './app/adms/Views/sst/partials/form_anexos.php'; ?>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-acidentes" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>