<?php
use App\adms\Helpers\CSRFHelper;
$csrf = CSRFHelper::generateCSRFToken('form_create_rh_personnel_request');
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nova Requisição de Pessoal</h2>
    </div>
    <div class="card border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Área *</label>
                        <select name="form[area_id]" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (($this->data['departments'] ?? []) as $dept): ?>
                                <option value="<?= (int) $dept['id'] ?>" <?= ((string) ($form['area_id'] ?? '') === (string) $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cargo *</label>
                        <select name="form[cargo_id]" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (($this->data['positions'] ?? []) as $pos): ?>
                                <option value="<?= (int) $pos['id'] ?>" <?= ((string) ($form['cargo_id'] ?? '') === (string) $pos['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string) ($pos['name'] ?? ''))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantidade</label>
                        <input type="number" min="1" name="form[quantidade]" class="form-control" value="<?= htmlspecialchars((string) ($form['quantidade'] ?? '1')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Contrato</label>
                        <select name="form[tipo_contrato]" class="form-select">
                            <?php foreach (['CLT', 'PJ', 'Estágio', 'Temporário'] as $tc): ?>
                                <option value="<?= $tc ?>" <?= ($form['tipo_contrato'] ?? 'CLT') === $tc ? 'selected' : '' ?>><?= $tc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Motivo</label>
                        <select name="form[motivo_tipo]" class="form-select">
                            <option value="aumento" <?= ($form['motivo_tipo'] ?? '') === 'aumento' ? 'selected' : '' ?>>Aumento de quadro</option>
                            <option value="reposicao" <?= ($form['motivo_tipo'] ?? '') === 'reposicao' ? 'selected' : '' ?>>Reposição</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data desejada</label>
                        <input type="date" name="form[data_desejada]" class="form-control" value="<?= htmlspecialchars((string) ($form['data_desejada'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salário mín.</label>
                        <input type="text" name="form[salario_min]" class="form-control" value="<?= htmlspecialchars((string) ($form['salario_min'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salário máx.</label>
                        <input type="text" name="form[salario_max]" class="form-control" value="<?= htmlspecialchars((string) ($form['salario_max'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Justificativa *</label>
                        <textarea name="form[justificativa]" class="form-control" rows="4" required><?= htmlspecialchars((string) ($form['justificativa'] ?? '')) ?></textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Enviar para aprovação</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
