<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_medicos_form');
$action = $isEdit ? 'sst-update-medico/' . (int)$item['id'] : 'sst-create-medico';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-user-md me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('Médico') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-medicos"><?= htmlspecialchars('Médicos') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
<label class="form-label" for="nome">Nome *</label>
<input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="crm">CRM</label>
<input type="text" name="crm" id="crm" class="form-control" value="<?= htmlspecialchars($item['crm'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="crm_uf">UF CRM</label>
<input type="text" name="crm_uf" id="crm_uf" class="form-control" value="<?= htmlspecialchars($item['crm_uf'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="clinica">Clínica</label>
<input type="text" name="clinica" id="clinica" class="form-control" value="<?= htmlspecialchars($item['clinica'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="telefone">Telefone</label>
<input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($item['telefone'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="email">E-mail</label>
<input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($item['email'] ?? '') ?>" >
</div>
<div class="col-md-6 mb-3">
<label class="form-label" for="status">Status</label>
<select name="status" id="status" class="form-select" >
<option value="Ativo" <?= (($item['status'] ?? '') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
<option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
</select>
</div>

                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-medicos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>