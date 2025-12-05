<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Metas de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Metas</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-bullseye me-2"></i>Metas de Desempenho (OKRs)</span>
            <div>
                <?php if (in_array('CreatePerformanceGoal', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-performance-goal" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Nova Meta
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="employee_id" class="form-label small">Colaborador</label>
                    <select name="employee_id" id="employee_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['employees'] ?? [] as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($this->data['filters']['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label small">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pending" <?= ($this->data['filters']['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pendente</option>
                        <option value="in_progress" <?= ($this->data['filters']['status'] ?? '') == 'in_progress' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="achieved" <?= ($this->data['filters']['status'] ?? '') == 'achieved' ? 'selected' : '' ?>>Alcançada</option>
                        <option value="failed" <?= ($this->data['filters']['status'] ?? '') == 'failed' ? 'selected' : '' ?>>Não Alcançada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="goal_type" class="form-label small">Tipo</label>
                    <select name="goal_type" id="goal_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="individual" <?= ($this->data['filters']['goal_type'] ?? '') == 'individual' ? 'selected' : '' ?>>Individual</option>
                        <option value="team" <?= ($this->data['filters']['goal_type'] ?? '') == 'team' ? 'selected' : '' ?>>Equipe</option>
                        <option value="company" <?= ($this->data['filters']['goal_type'] ?? '') == 'company' ? 'selected' : '' ?>>Empresa</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label small">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" 
                           placeholder="Título da meta..." value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals?limpar=1" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>

            <!-- Tabela -->
            <?php if (empty($this->data['goals'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhuma meta encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Colaborador</th>
                                <th>Tipo</th>
                                <th>Progresso</th>
                                <th>Prazo</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['goals'] as $goal): 
                                $progress = (int)($goal['progress_percentage'] ?? 0);
                                $statusLabels = [
                                    'pending' => ['label' => 'Pendente', 'color' => 'secondary'],
                                    'in_progress' => ['label' => 'Em Andamento', 'color' => 'info'],
                                    'achieved' => ['label' => 'Alcançada', 'color' => 'success'],
                                    'failed' => ['label' => 'Não Alcançada', 'color' => 'danger']
                                ];
                                $statusInfo = $statusLabels[$goal['status']] ?? ['label' => $goal['status'], 'color' => 'secondary'];
                                
                                $typeLabels = [
                                    'individual' => 'Individual',
                                    'team' => 'Equipe',
                                    'company' => 'Empresa'
                                ];
                                $typeLabel = $typeLabels[$goal['goal_type']] ?? $goal['goal_type'];
                                
                                $deadlineClass = '';
                                if (!empty($goal['deadline'])) {
                                    $deadlineDate = new \DateTime($goal['deadline']);
                                    $today = new \DateTime();
                                    if ($deadlineDate < $today && $goal['status'] !== 'achieved') {
                                        $deadlineClass = 'text-danger';
                                    } elseif ($deadlineDate->diff($today)->days <= 7) {
                                        $deadlineClass = 'text-warning';
                                    }
                                }
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($goal['goal_title']) ?></strong>
                                        <?php if (!empty($goal['goal_description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(mb_substr($goal['goal_description'], 0, 50)) ?><?= mb_strlen($goal['goal_description']) > 50 ? '...' : '' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($goal['employee_name'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $typeLabel ?></span>
                                    </td>
                                    <td style="width: 200px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-grow-1">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?= $progress >= 100 ? 'bg-success' : ($progress >= 70 ? 'bg-info' : ($progress >= 40 ? 'bg-warning' : 'bg-danger')) ?>" 
                                                         role="progressbar" 
                                                         style="width: <?= $progress ?>%"
                                                         aria-valuenow="<?= $progress ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?= $progress ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($goal['target_value']) && !empty($goal['current_value'])): ?>
                                                <small class="text-muted">
                                                    <?= number_format($goal['current_value'], 0) ?>/<?= number_format($goal['target_value'], 0) ?>
                                                    <?= !empty($goal['unit']) ? htmlspecialchars($goal['unit']) : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="<?= $deadlineClass ?>">
                                        <?php if (!empty($goal['deadline'])): ?>
                                            <?= date('d/m/Y', strtotime($goal['deadline'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusInfo['color'] ?>">
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('ViewPerformanceGoal', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-goal/<?= $goal['id'] ?>" 
                                                   class="btn btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('UpdatePerformanceGoal', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-goal/<?= $goal['id'] ?>" 
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if (!empty($this->data['pagination'])): ?>
                    <div class="mt-3">
                        <?= $this->data['pagination'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

