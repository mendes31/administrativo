<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_inspecoes_form');
$action = $isEdit ? 'sst-update-inspecao/' . (int)$item['id'] : 'sst-create-inspecao';
$perms = $this->data['buttonPermission'] ?? [];
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-search me-2"></i><?= $isEdit ? 'Editar' : 'Nova' ?> inspeção</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-inspecoes">Inspeções</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nova' ?></li>
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
                        <label class="form-label" for="tipo">Tipo</label>
                        <select name="tipo" id="tipo" class="form-select">
                            <?php foreach (['Rotina', 'Especial', 'CIPA', 'Outra'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($item['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="data_inspecao">Data *</label>
                        <input type="date" name="data_inspecao" id="data_inspecao" class="form-control" value="<?= htmlspecialchars($item['data_inspecao'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach (['Aberta', 'Em tratamento', 'Encerrada'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($item['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="adms_department_id">Departamento</label>
                        <select name="adms_department_id" id="adms_department_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($this->data['departments'] ?? [] as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= ((int)($item['adms_department_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="inspetor_adms_user_id">Inspetor</label>
                        <select name="inspetor_adms_user_id" id="inspetor_adms_user_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= ((int)($item['inspetor_adms_user_id'] ?? 0) === (int)$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="local">Local</label>
                        <input type="text" name="local" id="local" class="form-control" value="<?= htmlspecialchars($item['local'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="participantes">Participantes</label>
                        <input type="text" name="participantes" id="participantes" class="form-control" value="<?= htmlspecialchars($item['participantes'] ?? '') ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="conclusao">Conclusão</label>
                        <textarea name="conclusao" id="conclusao" class="form-control" rows="2"><?= htmlspecialchars($item['conclusao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-inspecoes" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
