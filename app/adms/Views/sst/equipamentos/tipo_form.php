<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamento_tipo_form');
$action = $isEdit ? 'sst-update-equipamento-tipo/' . (int)$item['id'] : 'sst-create-equipamento-tipo';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-layer-group me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> tipo de equipamento</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamento-tipos">Tipos</a></li>
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
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="codigo">Código *</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= ($item['status'] ?? 'Ativo') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($item['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamento-tipos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
