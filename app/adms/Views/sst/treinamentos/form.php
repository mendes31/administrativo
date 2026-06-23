<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstTreinamentoNrHelper;

$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_treinamentos_form');
$action = $isEdit ? 'sst-update-treinamento/' . (int)$item['id'] : 'sst-create-treinamento';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-graduation-cap me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> Treinamento SST</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos">Treinamentos</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="codigo">Código interno</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-uppercase" maxlength="20" value="<?= htmlspecialchars($item['codigo'] ?? '') ?>">
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="nome">Nome *</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="nr_referencia">NR referência</label>
                        <select name="nr_referencia" id="nr_referencia" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach (SstTreinamentoNrHelper::all() as $nr): ?>
                                <option value="<?= htmlspecialchars($nr) ?>" <?= (($item['nr_referencia'] ?? '') === $nr) ? 'selected' : '' ?>><?= htmlspecialchars($nr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select name="tipo" id="tipo" class="form-select">
                            <?php foreach (['Inicial', 'Reciclagem', 'Ambos'] as $t): ?>
                                <option value="<?= $t ?>" <?= (($item['tipo'] ?? 'Ambos') === $t) ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modalidade">Modalidade</label>
                        <select name="modalidade" id="modalidade" class="form-select">
                            <?php foreach (['Presencial', 'EAD', 'Hibrido'] as $m): ?>
                                <option value="<?= $m ?>" <?= (($item['modalidade'] ?? 'Presencial') === $m) ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="carga_horaria_minutos">Carga horária (minutos)</label>
                        <input type="number" name="carga_horaria_minutos" id="carga_horaria_minutos" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['carga_horaria_minutos'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="validade_meses">Validade reciclagem (meses)</label>
                        <input type="number" name="validade_meses" id="validade_meses" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['validade_meses'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="prazo_primeiro_dias">Prazo 1º treinamento (dias)</label>
                        <input type="number" name="prazo_primeiro_dias" id="prazo_primeiro_dias" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['prazo_primeiro_dias'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="Ativo" <?= (($item['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($item['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
