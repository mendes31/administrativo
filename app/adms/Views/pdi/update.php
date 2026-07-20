<?php
use App\adms\Helpers\CSRFHelper;
$plan = $this->data['plan'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar PDI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-pdi-plans" class="text-decoration-none">PDI</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><span><i class="fas fa-edit me-2"></i><?= htmlspecialchars($plan['title'] ?? '') ?></span></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_pdi_plan'); ?>">

                <div class="col-md-6">
                    <label for="user_id" class="form-label">Colaborador <span class="text-danger">*</span></label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach (($this->data['employees'] ?? []) as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>"
                                <?= ((int) ($plan['user_id'] ?? 0) === (int) $employee['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($employee['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="manager_id" class="form-label">Gestor responsável</label>
                    <select name="manager_id" id="manager_id" class="form-select">
                        <option value="">Opcional</option>
                        <?php foreach (($this->data['employees'] ?? []) as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>"
                                <?= ((int) ($plan['manager_id'] ?? 0) === (int) $employee['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($employee['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" required maxlength="255"
                           value="<?= htmlspecialchars($plan['title'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="performance_cycle_id" class="form-label">Ciclo</label>
                    <select name="performance_cycle_id" id="performance_cycle_id" class="form-select">
                        <option value="">Sem ciclo</option>
                        <?php foreach (($this->data['cycles'] ?? []) as $cycle): ?>
                            <option value="<?= (int) $cycle['id'] ?>"
                                <?= ((int) ($plan['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?> (<?= htmlspecialchars($cycle['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-3">
                    <label for="period_start" class="form-label">Início <span class="text-danger">*</span></label>
                    <input type="date" name="period_start" id="period_start" class="form-control" required
                           value="<?= htmlspecialchars($plan['period_start'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="period_end" class="form-label">Fim <span class="text-danger">*</span></label>
                    <input type="date" name="period_end" id="period_end" class="form-control" required
                           value="<?= htmlspecialchars($plan['period_end'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <?php foreach (['draft' => 'Rascunho', 'active' => 'Ativo', 'completed' => 'Concluído', 'cancelled' => 'Cancelado'] as $code => $label): ?>
                            <option value="<?= $code ?>" <?= ($plan['status'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="current_level" class="form-label">Nível atual</label>
                    <input type="text" name="current_level" id="current_level" class="form-control" maxlength="100"
                           value="<?= htmlspecialchars($plan['current_level'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="target_level" class="form-label">Nível alvo</label>
                    <input type="text" name="target_level" id="target_level" class="form-control" maxlength="100"
                           value="<?= htmlspecialchars($plan['target_level'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="career_goal" class="form-label">Objetivo de carreira</label>
                    <input type="text" name="career_goal" id="career_goal" class="form-control"
                           value="<?= htmlspecialchars($plan['career_goal'] ?? '') ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-pdi-plan/<?= (int) ($plan['id'] ?? 0) ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
