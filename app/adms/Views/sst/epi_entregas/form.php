<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_epi_entregas_form');
$action = $isEdit ? 'sst-update-epi-entrega/' . (int)$item['id'] : 'sst-create-epi-entrega';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-hand-holding me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('Entrega de EPI') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-entregas"><?= htmlspecialchars('Entregas de EPI') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
<label class="form-label" for="adms_user_id">Colaborador *</label>
<select name="adms_user_id" id="adms_user_id" class="form-select" required><option value="">Selecione...</option>
<?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>" <?= ((int)($item['adms_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="adms_sst_epi_id">EPI *</label>
<select name="adms_sst_epi_id" id="adms_sst_epi_id" class="form-select" required><option value="">Selecione...</option>
<?php foreach ($this->data['epis'] ?? [] as $x): ?><option value="<?= (int)$x['id'] ?>" <?= ((int)($item['adms_sst_epi_id'] ?? 0) === (int)$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars($x['nome'] ?? '') ?></option><?php endforeach; ?>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="tipo_movimento">Movimento</label>
<select name="tipo_movimento" id="tipo_movimento" class="form-select" >
<option value="Entrega" <?= (($item['tipo_movimento'] ?? '') === 'Entrega') ? 'selected' : '' ?>>Entrega</option>
<option value="Devolução" <?= (($item['tipo_movimento'] ?? '') === 'Devolução') ? 'selected' : '' ?>>Devolução</option>
<option value="Substituição" <?= (($item['tipo_movimento'] ?? '') === 'Substituição') ? 'selected' : '' ?>>Substituição</option>
<option value="Perda/Dano" <?= (($item['tipo_movimento'] ?? '') === 'Perda/Dano') ? 'selected' : '' ?>>Perda/Dano</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="quantidade">Quantidade</label>
<input type="number" name="quantidade" id="quantidade" class="form-control" value="<?= htmlspecialchars($item['quantidade'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_movimento">Data *</label>
<input type="date" name="data_movimento" id="data_movimento" class="form-control" value="<?= htmlspecialchars($item['data_movimento'] ?? '') ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="data_prevista_troca">Prev. troca</label>
<input type="date" name="data_prevista_troca" id="data_prevista_troca" class="form-control" value="<?= htmlspecialchars($item['data_prevista_troca'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="termo_assinado">Termo assinado</label>
<div class="form-check"><input type="checkbox" name="termo_assinado" id="termo_assinado" class="form-check-input" value="1" <?= !empty($item['termo_assinado']) ? 'checked' : '' ?>><label class="form-check-label" for="termo_assinado">Termo assinado</label></div>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="observacoes">Observações</label>
<textarea name="observacoes" id="observacoes" class="form-control" rows="3" ><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
</div>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-entregas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>