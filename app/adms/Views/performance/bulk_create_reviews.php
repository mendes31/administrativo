<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;

$cycle = $this->data['cycle'] ?? [];
$form = $this->data['form'] ?? [];
$fullAccess = UserAccessHelper::hasFullSystemAccess();
$cycleId = (int) ($cycle['id'] ?? 0);
$status = (string) ($cycle['status'] ?? '');
$canGenerate = in_array($status, ['draft', 'open'], true);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Gerar Avaliações em Massa</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-cycles" class="text-decoration-none">Ciclos</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-cycle/<?= $cycleId ?>" class="text-decoration-none">
                    <?= htmlspecialchars($cycle['name'] ?? 'Ciclo') ?>
                </a>
            </li>
            <li class="breadcrumb-item">Gerar avaliações</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if (!$canGenerate): ?>
        <div class="alert alert-warning">
            Ciclo fechado — não é possível gerar novas avaliações. Abra um ciclo em rascunho ou aberto.
        </div>
    <?php endif; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <i class="fas fa-layer-group me-2"></i>
            Ciclo: <?= htmlspecialchars($cycle['name'] ?? '') ?>
            (<?= htmlspecialchars($cycle['period_start'] ?? '') ?> — <?= htmlspecialchars($cycle['period_end'] ?? '') ?>)
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>bulk-create-performance-reviews/<?= $cycleId ?>">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_bulk_create_performance_reviews') ?>">
                <input type="hidden" name="performance_cycle_id" value="<?= $cycleId ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="review_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="review_type" id="review_type" class="form-select" required <?= $canGenerate ? '' : 'disabled' ?>>
                            <?php foreach (['90' => '90°', '180' => '180°', '360' => '360°', 'annual' => 'Anual'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= (($form['review_type'] ?? '') === $val) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="scope" class="form-label">Escopo <span class="text-danger">*</span></label>
                        <select name="scope" id="scope" class="form-select" required <?= $canGenerate ? '' : 'disabled' ?>>
                            <?php if ($fullAccess): ?>
                                <option value="all_active" <?= (($form['scope'] ?? '') === 'all_active') ? 'selected' : '' ?>>
                                    Todos os ativos
                                </option>
                                <option value="department" <?= (($form['scope'] ?? '') === 'department') ? 'selected' : '' ?>>
                                    Por departamento
                                </option>
                            <?php endif; ?>
                            <option value="manager" <?= (($form['scope'] ?? '') === 'manager') ? 'selected' : '' ?>>
                                Subordinados de um gestor
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4" id="wrap-department">
                        <label for="department_id" class="form-label">Departamento</label>
                        <select name="department_id" id="department_id" class="form-select" <?= $canGenerate ? '' : 'disabled' ?>>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['departments'] ?? [] as $dep): ?>
                                <option value="<?= (int) $dep['id'] ?>"
                                    <?= ((string) ($form['department_id'] ?? '') === (string) $dep['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4" id="wrap-manager">
                        <label for="manager_id" class="form-label">Gestor</label>
                        <select name="manager_id" id="manager_id" class="form-select" <?= $canGenerate ? '' : 'disabled' ?>>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['managers'] ?? [] as $mgr): ?>
                                <option value="<?= (int) $mgr['id'] ?>"
                                    <?= ((string) ($form['manager_id'] ?? '') === (string) $mgr['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mgr['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="reviewer_mode" class="form-label">Avaliador</label>
                        <select name="reviewer_mode" id="reviewer_mode" class="form-select" <?= $canGenerate ? '' : 'disabled' ?>>
                            <option value="supervisor" <?= (($form['reviewer_mode'] ?? '') === 'supervisor') ? 'selected' : '' ?>>
                                Gestor imediato do colaborador
                            </option>
                            <option value="fixed" <?= (($form['reviewer_mode'] ?? '') === 'fixed') ? 'selected' : '' ?>>
                                Avaliador fixo
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4" id="wrap-reviewer">
                        <label for="reviewer_id" class="form-label">Avaliador fixo</label>
                        <select name="reviewer_id" id="reviewer_id" class="form-select" <?= $canGenerate ? '' : 'disabled' ?>>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['managers'] ?? [] as $mgr): ?>
                                <option value="<?= (int) $mgr['id'] ?>"
                                    <?= ((string) ($form['reviewer_id'] ?? '') === (string) $mgr['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mgr['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="hidden" name="skip_existing" value="0">
                            <input class="form-check-input" type="checkbox" name="skip_existing" id="skip_existing" value="1"
                                <?= (($form['skip_existing'] ?? '1') !== '0') ? 'checked' : '' ?>
                                <?= $canGenerate ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="skip_existing">
                                Pular se já existir avaliação do mesmo tipo no ciclo
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <?php if ($canGenerate): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play me-1"></i>Gerar avaliações
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-cycle/<?= $cycleId ?>" class="btn btn-secondary">
                        Voltar ao ciclo
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const scope = document.getElementById('scope');
    const reviewerMode = document.getElementById('reviewer_mode');
    const wrapDept = document.getElementById('wrap-department');
    const wrapMgr = document.getElementById('wrap-manager');
    const wrapRev = document.getElementById('wrap-reviewer');
    function sync() {
        const s = scope ? scope.value : '';
        const m = reviewerMode ? reviewerMode.value : '';
        if (wrapDept) wrapDept.style.display = s === 'department' ? '' : 'none';
        if (wrapMgr) wrapMgr.style.display = s === 'manager' ? '' : 'none';
        if (wrapRev) wrapRev.style.display = m === 'fixed' ? '' : 'none';
    }
    if (scope) scope.addEventListener('change', sync);
    if (reviewerMode) reviewerMode.addEventListener('change', sync);
    sync();
})();
</script>
