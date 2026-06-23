<?php
use App\adms\Helpers\CSRFHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_ghe_form');
$action = $isEdit ? 'sst-update-ghe/' . (int)$item['id'] : 'sst-create-ghe';
$departments = $this->data['departments'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-industry me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> GHE</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-ghe">GHE</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="codigo">Código</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" maxlength="20" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>">
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="ambiente_local">Ambiente / local</label>
                        <input type="text" name="ambiente_local" id="ambiente_local" class="form-control" value="<?= htmlspecialchars($item['ambiente_local'] ?? '') ?>" placeholder="Ex.: Galpão A, Linha 2">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="adms_department_id">Departamento</label>
                        <select name="adms_department_id" id="adms_department_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($departments as $dep): ?>
                                <option value="<?= (int)($dep['id'] ?? 0) ?>" <?= (string)($item['adms_department_id'] ?? '') === (string)($dep['id'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= ($item['status'] ?? 'Ativo') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($item['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?><?= $isEdit ? 'sst-view-ghe/' . (int)$item['id'] : 'sst-list-ghe' ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
