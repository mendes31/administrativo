<?php
$viewMode = $this->data['view_mode'] ?? 'list';
$isGestor = $this->data['is_gestor'] ?? false;
$filters = $this->data['filters'] ?? [];
$activities = $this->data['activities'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-calendar-alt text-primary me-2"></i>
            Agenda de Atividades
        </h1>
        <div class="btn-group">
            <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities?view=list" 
               class="btn btn-sm btn-<?= $viewMode === 'list' ? 'primary' : 'outline-primary' ?>">
                <i class="fas fa-list me-1"></i>Lista
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities?view=calendar" 
               class="btn btn-sm btn-<?= $viewMode === 'calendar' ? 'primary' : 'outline-primary' ?>">
                <i class="fas fa-calendar me-1"></i>Calendário
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="view" value="<?= $viewMode ?>">
                <div class="row g-3">
                    <?php if ($isGestor): ?>
                    <div class="col-md-3">
                        <label class="form-label">Responsável</label>
                        <select name="responsible_user_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['users'] as $user): ?>
                                <option value="<?= $user['id'] ?>" 
                                        <?= ($filters['responsible_user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['activity_types'] as $type): ?>
                                <option value="<?= $type ?>" 
                                        <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>>
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($this->data['statuses'] as $status): ?>
                                <option value="<?= $status ?>" 
                                        <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">De</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Até</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($viewMode === 'calendar'): ?>
        <!-- VISUALIZAÇÃO: CALENDÁRIO -->
        <?php include './app/adms/Views/crm/activities/calendar.php'; ?>
    <?php else: ?>
        <!-- VISUALIZAÇÃO: LISTA -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (!empty($activities)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-green">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Parceiro/Oportunidade</th>
                                    <th>Responsável</th>
                                    <th>Prioridade</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr class="<?= $activity['status'] === 'Concluída' ? 'table-success' : ($activity['priority'] === 'Urgente' ? 'table-danger' : '') ?>">
                                        <td>
                                            <?= $activity['scheduled_date'] ? date('d/m/Y H:i', strtotime($activity['scheduled_date'])) : 'N/A' ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-<?= $activity['type'] === 'Ligação' ? 'phone' : ($activity['type'] === 'Reunião' ? 'users' : ($activity['type'] === 'E-mail' ? 'envelope' : 'tasks')) ?> me-1"></i>
                                            <?= htmlspecialchars($activity['type']) ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                            <?php if ($activity['description']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($activity['description'], 0, 50)) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($activity['opportunity_title']): ?>
                                                <i class="fas fa-bullseye text-primary me-1"></i>
                                                <?= htmlspecialchars($activity['opportunity_title']) ?>
                                            <?php elseif ($activity['partner_name']): ?>
                                                <i class="fas fa-handshake text-success me-1"></i>
                                                <?= htmlspecialchars($activity['partner_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['responsible_name']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $activity['priority'] === 'Urgente' ? 'danger' : ($activity['priority'] === 'Alta' ? 'warning' : ($activity['priority'] === 'Média' ? 'info' : 'secondary')) ?>">
                                                <?= htmlspecialchars($activity['priority']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $activity['status'] === 'Concluída' ? 'success' : ($activity['status'] === 'Pendente' ? 'warning' : 'secondary') ?>">
                                                <?= htmlspecialchars($activity['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($activity['status'] === 'Pendente'): ?>
                                                <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-complete-activity/<?= $activity['id'] ?>" style="display:inline;">
                                                    <input type="hidden" name="redirect_to" value="crm-list-activities">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Concluir">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-activity/<?= $activity['id'] ?>" 
                                               class="btn btn-sm btn-danger" title="Excluir"
                                               onclick="return confirm('Tem certeza que deseja excluir esta atividade?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma atividade encontrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

