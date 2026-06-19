<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEpiCategoriaHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_epis_form');
$action = $isEdit ? 'sst-update-epi/' . (int)$item['id'] : 'sst-create-epi';
$categoriaSelecionada = $item['categoria'] ?? '';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-hard-hat me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('EPI') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis"><?= htmlspecialchars('EPIs') ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <h6 class="text-muted text-uppercase small mb-3">Dados básicos</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="categoria">Categoria de proteção *</label>
                        <select name="categoria" id="categoria" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (SstEpiCategoriaHelper::all() as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $categoriaSelecionada === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                        <div class="form-text">Detalhes técnicos do item. O Nº CA é informado nas movimentações de estoque.</div>
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small mb-3 mt-2">Estoque e controle</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="estoque_minimo">Estoque mínimo (alerta de compra)</label>
                        <input type="number" name="estoque_minimo" id="estoque_minimo" class="form-control" min="0" value="<?= htmlspecialchars((string)($item['estoque_minimo'] ?? '')) ?>">
                        <div class="form-text">Saldo agregado do EPI, independente de CA ou fabricante.</div>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Saldo atual</label>
                        <input type="text" class="form-control" value="<?= (int)($item['estoque_atual'] ?? 0) ?>" readonly disabled>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento?adms_sst_epi_id=<?= (int)$item['id'] ?>&tipo=Entrada" class="btn btn-sm btn-outline-success mt-1">Registrar entrada</a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="periodicidade_troca_dias">Vida útil padrão (dias)</label>
                        <input type="number" name="periodicidade_troca_dias" id="periodicidade_troca_dias" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['periodicidade_troca_dias'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= (($item['status'] ?? '') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epis" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
