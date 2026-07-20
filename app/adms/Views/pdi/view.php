<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\FormatHelper;

$plan = $this->data['plan'] ?? [];
$statusLabels = [
    'draft' => ['label' => 'Rascunho', 'color' => 'secondary'],
    'active' => ['label' => 'Ativo', 'color' => 'success'],
    'completed' => ['label' => 'Concluído', 'color' => 'primary'],
    'cancelled' => ['label' => 'Cancelado', 'color' => 'dark'],
];
$actionTypeLabels = [
    'training' => 'Treinamento',
    'course' => 'Curso',
    'mentoring' => 'Mentoria',
    'project' => 'Projeto',
    'reading' => 'Leitura',
    'other' => 'Outro',
];
$actionStatusLabels = [
    'pending' => 'Pendente',
    'in_progress' => 'Em andamento',
    'completed' => 'Concluída',
    'cancelled' => 'Cancelada',
];
$st = $statusLabels[$plan['status'] ?? ''] ?? ['label' => $plan['status'] ?? '-', 'color' => 'secondary'];
$canEdit = in_array('UpdatePdiPlan', $this->data['buttonPermission'] ?? [], true);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">PDI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-pdi-plans" class="text-decoration-none">PDI</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-user-graduate me-2"></i><?= htmlspecialchars($plan['title'] ?? '') ?></span>
            <div>
                <?php if ($canEdit) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-pdi-plan/<?= (int) $plan['id'] ?>" class="btn btn-sm btn-warning">Editar plano</a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-2">
                <div class="col-md-3"><div class="text-muted small">Colaborador</div><?= htmlspecialchars($plan['user_name'] ?? '') ?></div>
                <div class="col-md-3"><div class="text-muted small">Gestor</div><?= htmlspecialchars($plan['manager_name'] ?? '—') ?></div>
                <div class="col-md-3"><div class="text-muted small">Ciclo</div><?= htmlspecialchars($plan['cycle_name'] ?? '—') ?></div>
                <div class="col-md-3"><div class="text-muted small">Status</div><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></div>
                <div class="col-md-3"><div class="text-muted small">Período</div>
                    <?= htmlspecialchars(FormatHelper::formatDate($plan['period_start'] ?? '')) ?> —
                    <?= htmlspecialchars(FormatHelper::formatDate($plan['period_end'] ?? '')) ?>
                </div>
                <div class="col-md-3"><div class="text-muted small">Nível atual → alvo</div>
                    <?= htmlspecialchars($plan['current_level'] ?? '—') ?> → <?= htmlspecialchars($plan['target_level'] ?? '—') ?>
                </div>
                <?php if (!empty($plan['career_goal'])): ?>
                    <div class="col-md-6"><div class="text-muted small">Objetivo de carreira</div><?= htmlspecialchars($plan['career_goal']) ?></div>
                <?php endif; ?>
                <?php if (!empty($plan['description'])): ?>
                    <div class="col-12"><div class="text-muted small">Descrição</div><?= nl2br(htmlspecialchars($plan['description'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-tasks me-2"></i>Ações de desenvolvimento</div>
        <div class="card-body">
            <?php if (empty($this->data['actions'])): ?>
                <div class="alert alert-info">Nenhuma ação cadastrada ainda.</div>
            <?php else: ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover align-middle">
                        <thead><tr>
                            <th>Título</th><th>Tipo</th><th>Treinamento</th><th>Prioridade</th><th>Status</th><th>%</th><th></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($this->data['actions'] as $action): ?>
                                <tr>
                                    <td><?= htmlspecialchars($action['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actionTypeLabels[$action['action_type'] ?? ''] ?? ($action['action_type'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($action['training_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($action['priority'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($actionStatusLabels[$action['status'] ?? ''] ?? ($action['status'] ?? '')) ?></td>
                                    <td><?= (int) ($action['progress_percentage'] ?? 0) ?>%</td>
                                    <td>
                                        <?php if ($canEdit): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#edit-action-<?= (int) $action['id'] ?>">Editar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($canEdit): ?>
                                <tr class="collapse" id="edit-action-<?= (int) $action['id'] ?>">
                                    <td colspan="7">
                                        <form method="POST" class="row g-2 border rounded p-3 bg-light">
                                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_pdi_update_action'); ?>">
                                            <input type="hidden" name="form_action" value="update_action">
                                            <input type="hidden" name="action_id" value="<?= (int) $action['id'] ?>">
                                            <div class="col-md-4">
                                                <label class="form-label small">Título</label>
                                                <input type="text" name="title" class="form-control form-control-sm" required
                                                       value="<?= htmlspecialchars($action['title'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Tipo</label>
                                                <select name="action_type" class="form-select form-select-sm">
                                                    <?php foreach ($actionTypeLabels as $code => $label): ?>
                                                        <option value="<?= $code ?>" <?= ($action['action_type'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Treinamento</label>
                                                <select name="training_id" class="form-select form-select-sm">
                                                    <option value="">Nenhum</option>
                                                    <?php foreach ($this->data['trainings'] ?? [] as $t): ?>
                                                        <option value="<?= (int) $t['id'] ?>"
                                                            <?= ((int) ($action['training_id'] ?? 0) === (int) $t['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($t['name'] ?? '') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <?php foreach ($actionStatusLabels as $code => $label): ?>
                                                        <option value="<?= $code ?>" <?= ($action['status'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">%</label>
                                                <input type="number" name="progress_percentage" class="form-control form-control-sm" min="0" max="100"
                                                       value="<?= (int) ($action['progress_percentage'] ?? 0) ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Prioridade</label>
                                                <select name="priority" class="form-select form-select-sm">
                                                    <?php foreach (['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta'] as $code => $label): ?>
                                                        <option value="<?= $code ?>" <?= ($action['priority'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Início</label>
                                                <input type="date" name="start_date" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars($action['start_date'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Fim</label>
                                                <input type="date" name="end_date" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars($action['end_date'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Descrição</label>
                                                <input type="text" name="description" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars($action['description'] ?? '') ?>">
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-sm btn-success">Salvar ação</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
                <h6 class="mb-2">Nova ação</h6>
                <form method="POST" class="row g-2 border rounded p-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_pdi_add_action'); ?>">
                    <input type="hidden" name="form_action" value="add_action">
                    <div class="col-md-4">
                        <label class="form-label small">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Tipo</label>
                        <select name="action_type" class="form-select form-select-sm">
                            <?php foreach ($actionTypeLabels as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $code === 'training' ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Treinamento do catálogo</label>
                        <select name="training_id" class="form-select form-select-sm">
                            <option value="">Nenhum</option>
                            <?php foreach ($this->data['trainings'] ?? [] as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Prioridade</label>
                        <select name="priority" class="form-select form-select-sm">
                            <option value="low">Baixa</option>
                            <option value="medium" selected>Média</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="pending" selected>Pendente</option>
                            <option value="in_progress">Em andamento</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Adicionar ação</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-brain me-2"></i>Competências</div>
        <div class="card-body">
            <?php if (empty($this->data['competencies'])): ?>
                <div class="alert alert-info">Nenhuma competência vinculada.</div>
            <?php else: ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Competência</th><th>Tipo</th><th>Atual</th><th>Alvo</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($this->data['competencies'] as $comp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($comp['competency_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($comp['competency_type'] ?? '') ?></td>
                                    <td><?= (int) ($comp['current_level'] ?? 0) ?></td>
                                    <td><?= (int) ($comp['target_level'] ?? 0) ?></td>
                                    <td>
                                        <?php if ($canEdit): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Remover esta competência?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_pdi_remove_competency'); ?>">
                                                <input type="hidden" name="form_action" value="remove_competency">
                                                <input type="hidden" name="competency_row_id" value="<?= (int) $comp['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
                <h6 class="mb-2">Vincular competência</h6>
                <form method="POST" class="row g-2 border rounded p-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_pdi_add_competency'); ?>">
                    <input type="hidden" name="form_action" value="add_competency">
                    <div class="col-md-5">
                        <label class="form-label small">Do catálogo</label>
                        <select name="competency_id" class="form-select form-select-sm">
                            <option value="">Selecionar...</option>
                            <?php foreach ($this->data['catalog_competencies'] ?? [] as $c): ?>
                                <option value="<?= (int) $c['id'] ?>">
                                    <?= htmlspecialchars($c['name'] ?? '') ?> (<?= htmlspecialchars($c['competency_type'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Ou nome livre</label>
                        <input type="text" name="competency_name" class="form-control form-control-sm" placeholder="Se não usar catálogo">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Tipo (livre)</label>
                        <select name="competency_type" class="form-select form-select-sm">
                            <option value="technical">Técnica</option>
                            <option value="behavioral" selected>Comportamental</option>
                            <option value="leadership">Liderança</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Atual</label>
                        <input type="number" name="current_level" class="form-control form-control-sm" min="1" max="5" value="1">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Alvo</label>
                        <input type="number" name="target_level" class="form-control form-control-sm" min="1" max="5" value="3">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Vincular</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
