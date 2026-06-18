<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCidCapituloHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_cids_form');
$action = $isEdit ? 'sst-update-cid/' . (int)$item['id'] : 'sst-create-cid';
$cap = SstCidCapituloHelper::resolveFromCodigo((string)($item['codigo'] ?? ''));
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-notes-medical me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> CID</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-cids">CIDs</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="codigo">Código *</label>
                        <input type="text" name="codigo" id="codigo" class="form-control" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>" required placeholder="Ex.: M54.5">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="descricao">Descrição *</label>
                        <input type="text" name="descricao" id="descricao" class="form-control" value="<?= htmlspecialchars($item['descricao'] ?? '') ?>" required>
                    </div>
                    <?php if ($isEdit && !empty($item['capitulo_nome'])): ?>
                    <div class="col-md-12 mb-3">
                        <div class="alert alert-light border mb-0 py-2">
                            <strong>Capítulo <?= (int)($item['capitulo_num'] ?? 0) ?>:</strong>
                            <?= htmlspecialchars($item['capitulo_nome'] ?? '') ?>
                            <?php if (!empty($item['categoria'])): ?>
                                <span class="text-muted">| Categoria <?= htmlspecialchars($item['categoria']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif (!$isEdit): ?>
                    <div class="col-md-12 mb-3">
                        <div class="form-text">O capítulo CID-10 será calculado automaticamente ao salvar.</div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= (($item['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-block">Uso frequente</label>
                        <div class="form-check">
                            <input type="checkbox" name="frequente" id="frequente" class="form-check-input" value="1" <?= !empty($item['frequente']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="frequente">Destacar nas buscas de afastamento/acidente</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-cids" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
