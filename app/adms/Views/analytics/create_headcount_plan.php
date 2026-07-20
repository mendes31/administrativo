<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nova Linha de Quadro</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-headcount-plans" class="text-decoration-none">Quadro</a></li>
            <li class="breadcrumb-item">Nova</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_create_headcount_plan') ?>">
                <div class="col-md-6">
                    <label class="form-label">Departamento *</label>
                    <select name="department_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach (($this->data['departments'] ?? []) as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (string)($form['department_id'] ?? '') === (string)$d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cargo (opcional)</label>
                    <select name="position_id" class="form-select">
                        <option value="">Toda a área</option>
                        <?php foreach (($this->data['positions'] ?? []) as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (string)($form['position_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ano *</label>
                    <input type="number" name="period_year" class="form-control" required
                           value="<?= htmlspecialchars((string)($form['period_year'] ?? date('Y'))) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mês *</label>
                    <select name="period_month" class="form-select" required>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= (int)($form['period_month'] ?? date('n')) === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Qtd. planejada *</label>
                    <input type="number" min="0" name="planned_count" class="form-control" required
                           value="<?= htmlspecialchars((string)($form['planned_count'] ?? '0')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars((string)($form['notes'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-headcount-plans" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
