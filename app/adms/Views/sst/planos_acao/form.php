<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$acidente = $this->data['acidente'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_planos_acao_form');
$action = $isEdit ? 'sst-update-plano-acao/' . (int) $item['id'] : 'sst-create-plano-acao';
$acidenteId = (int) ($item['adms_sst_acidente_id'] ?? $acidente['id'] ?? 0);
$statusOptions = ['Pendente', 'Em andamento', 'Concluído', 'Cancelado'];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-tasks me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> plano de ação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-acidentes">Acidentes</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-view-acidente/<?= $acidenteId ?>">Acidente #<?= $acidenteId ?></a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="alert alert-light border small mb-3">
        Vinculado ao acidente #<?= $acidenteId ?>
        <?php if (!empty($acidente['colaborador_nome'])): ?> — <?= htmlspecialchars($acidente['colaborador_nome']) ?><?php endif; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM'] . $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="adms_sst_acidente_id" value="<?= $acidenteId ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="titulo">Título *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($item['titulo'] ?? '') ?>" required maxlength="255">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= (($item['status'] ?? 'Pendente') === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="descricao">Descrição da ação</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="responsavel_adms_user_id">Responsável</label>
                        <select name="responsavel_adms_user_id" id="responsavel_adms_user_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <option value="<?= (int) $u['id'] ?>" <?= ((int) ($item['responsavel_adms_user_id'] ?? 0) === (int) $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="prazo">Prazo</label>
                        <input type="date" name="prazo" id="prazo" class="form-control" value="<?= htmlspecialchars($item['prazo'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_conclusao">Data conclusão</label>
                        <input type="date" name="data_conclusao" id="data_conclusao" class="form-control" value="<?= htmlspecialchars($item['data_conclusao'] ?? '') ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-view-acidente/<?= $acidenteId ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
