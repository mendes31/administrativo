<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_cipa_form');
$action = $isEdit ? 'sst-update-cipa-mandato/' . (int)$item['id'] : 'sst-create-cipa-mandato';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-users-cog me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> mandato CIPA</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-cipa-mandatos">CIPA</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="titulo">Título *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($item['titulo'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= ($item['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Encerrado" <?= ($item['status'] ?? '') === 'Encerrado' ? 'selected' : '' ?>>Encerrado</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="data_inicio">Início *</label>
                        <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($item['data_inicio'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="data_fim">Fim</label>
                        <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($item['data_fim'] ?? '') ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-cipa-mandatos" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
