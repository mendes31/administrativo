<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
$closed = ($form['status'] ?? '') === 'closed';
$statusLabels = ['draft' => 'Rascunho', 'active' => 'Ativo', 'closed' => 'Fechado'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Linha de Quadro</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>view-headcount-plan/<?= (int)($form['id'] ?? 0) ?>" class="text-decoration-none">Detalhe</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <p class="text-muted small">
                <?= htmlspecialchars($form['department_name'] ?? '') ?>
                · <?= htmlspecialchars($form['position_name'] ?? 'Área') ?>
                · <?= (int)($form['period_month'] ?? 0) ?>/<?= (int)($form['period_year'] ?? 0) ?>
            </p>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_update_headcount_plan') ?>">
                <div class="col-md-4">
                    <label class="form-label">Qtd. planejada</label>
                    <input type="number" min="0" name="planned_count" class="form-control"
                           value="<?= htmlspecialchars((string)($form['planned_count'] ?? '0')) ?>"
                           <?= $closed ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" <?= $closed ? 'disabled' : '' ?>>
                        <?php foreach ($statusLabels as $k => $l): ?>
                            <option value="<?= $k ?>" <?= ($form['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($closed): ?>
                        <input type="hidden" name="status" value="closed">
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars((string)($form['notes'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>view-headcount-plan/<?= (int)($form['id'] ?? 0) ?>" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
