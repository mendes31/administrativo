<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$isEdit = !empty($item['id']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_treinamento_necessidade_form');
$action = $isEdit ? 'sst-update-treinamento-necessidade/' . (int)$item['id'] : 'sst-create-treinamento-necessidade';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <h2 class="mt-3"><i class="fas fa-clipboard-list me-2"></i><?= $isEdit ? 'Editar' : 'Nova' ?> Necessidade de Treinamento</h2>
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?><?= $action ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cargo</label>
                        <select name="adms_position_id" class="form-select"><option value="">Todos</option>
                        <?php foreach ($this->data['positions'] ?? [] as $p): ?><option value="<?= (int)$p['id'] ?>" <?= ((int)($item['adms_position_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name'] ?? '') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Departamento</label>
                        <select name="adms_department_id" class="form-select"><option value="">Todos</option>
                        <?php foreach ($this->data['departments'] ?? [] as $d): ?><option value="<?= (int)$d['id'] ?>" <?= ((int)($item['adms_department_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name'] ?? '') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Risco</label>
                        <select name="adms_sst_risco_id" class="form-select"><option value="">Opcional</option>
                        <?php foreach ($this->data['riscos'] ?? [] as $r): ?><option value="<?= (int)$r['id'] ?>" <?= ((int)($item['adms_sst_risco_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['nome'] ?? '') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Treinamento *</label>
                        <select name="adms_sst_treinamento_id" class="form-select" required><option value="">Selecione...</option>
                        <?php foreach ($this->data['treinamentos'] ?? [] as $t): ?><option value="<?= (int)$t['id'] ?>" <?= ((int)($item['adms_sst_treinamento_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['nome'] ?? '') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Validade (meses)</label>
                        <input type="number" name="validade_meses" class="form-control" min="1" value="<?= htmlspecialchars((string)($item['validade_meses'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="obrigatorio" class="form-check-input" value="1" id="obrigatorio" <?= !isset($item['obrigatorio']) || !empty($item['obrigatorio']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="obrigatorio">Obrigatório</label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-necessidade" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
