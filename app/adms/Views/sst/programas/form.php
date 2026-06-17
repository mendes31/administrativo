<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrf = CSRFHelper::generateCSRFToken('sst_programas_form');
$action = $isEdit ? 'sst-update-programa/' . (int)$item['id'] : 'sst-create-programa';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <h2 class="mt-3"><i class="fas fa-file-contract me-2"></i><?= $isEdit ? 'Editar' : 'Novo' ?> programa SST</h2>
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM'] . $action ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><?php endif; ?>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <?php foreach (['PGR', 'PCMSO', 'PPRA', 'LTCAT', 'Outro'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($item['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($item['titulo'] ?? '') ?>" required maxlength="255">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Versão</label>
                        <input type="text" name="versao" class="form-control" value="<?= htmlspecialchars($item['versao'] ?? '') ?>" placeholder="ex: 2024.1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Vigência início *</label>
                        <input type="date" name="vigencia_inicio" class="form-control" value="<?= htmlspecialchars($item['vigencia_inicio'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Vigência fim</label>
                        <input type="date" name="vigencia_fim" class="form-control" value="<?= htmlspecialchars($item['vigencia_fim'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Rascunho', 'Vigente', 'Revogado'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($item['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Responsável</label>
                        <select name="responsavel_adms_user_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (int)($item['responsavel_adms_user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Médico (PCMSO)</label>
                        <select name="adms_sst_medico_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($this->data['medicos'] ?? [] as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= (int)($item['adms_sst_medico_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Escopo — cargo</label>
                        <select name="adms_position_id" class="form-select">
                            <option value="">Empresa / todos</option>
                            <?php foreach ($this->data['positions'] ?? [] as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= (int)($item['adms_position_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Escopo — departamento</label>
                        <select name="adms_department_id" class="form-select">
                            <option value="">Empresa / todos</option>
                            <?php foreach ($this->data['departments'] ?? [] as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (int)($item['adms_department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                    <?php include './app/adms/Views/sst/partials/form_anexos.php'; ?>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-programas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
