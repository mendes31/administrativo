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
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNewActivity">
                <i class="fas fa-plus me-1"></i>Nova Atividade
            </button>
            <div class="btn-group">
                <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities?view=list" 
                   class="btn btn-sm btn-<?= $viewMode === 'list' ? 'primary' : 'outline-primary' ?>">
                    <i class="fas fa-list me-1"></i>Lista
                </a>
                <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities?view=calendar-month" 
                   class="btn btn-sm btn-<?= $viewMode === 'calendar-month' ? 'primary' : 'outline-primary' ?>">
                    <i class="fas fa-calendar me-1"></i>Mensal
                </a>
                <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities?view=calendar-week" 
                   class="btn btn-sm btn-<?= $viewMode === 'calendar-week' ? 'primary' : 'outline-primary' ?>">
                    <i class="fas fa-calendar-week me-1"></i>Semanal
                </a>
            </div>
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
                            <option value="call" <?= ($filters['type'] ?? '') === 'call' ? 'selected' : '' ?>>📞 Ligação</option>
                            <option value="meeting" <?= ($filters['type'] ?? '') === 'meeting' ? 'selected' : '' ?>>👥 Reunião</option>
                            <option value="email" <?= ($filters['type'] ?? '') === 'email' ? 'selected' : '' ?>>✉️ E-mail</option>
                            <option value="task" <?= ($filters['type'] ?? '') === 'task' ? 'selected' : '' ?>>✔️ Tarefa</option>
                            <option value="note" <?= ($filters['type'] ?? '') === 'note' ? 'selected' : '' ?>>📝 Nota</option>
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

    <?php if ($viewMode === 'calendar-month'): ?>
        <!-- VISUALIZAÇÃO: CALENDÁRIO MENSAL -->
        <?php include './app/adms/Views/crm/activities/calendar.php'; ?>
    <?php elseif ($viewMode === 'calendar-week'): ?>
        <!-- VISUALIZAÇÃO: CALENDÁRIO SEMANAL -->
        <?php include './app/adms/Views/crm/activities/calendar-week.php'; ?>
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
                                            <?php
                                            // Traduzir tipo de atividade (banco em inglês)
                                            $typeTranslations = [
                                                'call' => 'Ligação',
                                                'email' => 'E-mail',
                                                'meeting' => 'Reunião',
                                                'task' => 'Tarefa',
                                                'note' => 'Nota'
                                            ];
                                            $typeIcons = [
                                                'call' => 'phone',
                                                'email' => 'envelope',
                                                'meeting' => 'users',
                                                'task' => 'tasks',
                                                'note' => 'sticky-note'
                                            ];
                                            // Cores iguais ao cadastro (dropdown)
                                            $typeColors = [
                                                'call' => '#e91e63',      // Rosa/Pink (Ligação)
                                                'meeting' => '#9c27b0',   // Roxo (Reunião)
                                                'email' => '#ba68c8',     // Roxo claro/Lilás (E-mail)
                                                'task' => '#6a1b9a',      // Roxo escuro (Tarefa)
                                                'note' => '#ff9800'       // Laranja (Nota)
                                            ];
                                            $displayType = $typeTranslations[$activity['type']] ?? $activity['type'];
                                            $icon = $typeIcons[$activity['type']] ?? 'tasks';
                                            $color = $typeColors[$activity['type']] ?? '#6c757d';
                                            ?>
                                            <span class="badge" style="background-color: <?= $color ?>; font-size: 0.85rem; font-weight: 500;">
                                                <i class="fas fa-<?= $icon ?> me-1"></i>
                                                <?= htmlspecialchars($displayType) ?>
                                            </span>
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
                                            <a href="<?= $_ENV['URL_ADM'] ?>crm-view-activity/<?= $activity['id'] ?>" 
                                               class="btn btn-sm btn-info" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditActivity<?= $activity['id'] ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
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

<!-- Modal Nova Atividade -->
<div class="modal fade" id="modalNewActivity" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Nova Atividade
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-create-activity">
                <div class="modal-body">
                    <input type="hidden" name="redirect_to" value="crm-list-activities">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Atividade *</label>
                            <select name="type" class="form-select" required>
                                <option value="">Selecione...</option>
                                <option value="call">📞 Ligação</option>
                                <option value="meeting">👥 Reunião</option>
                                <option value="email">✉️ E-mail</option>
                                <option value="task">✔️ Tarefa</option>
                                <option value="note">📝 Nota</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prioridade *</label>
                            <select name="priority" class="form-select" required>
                                <option value="Baixa">Baixa</option>
                                <option value="Média" selected>Média</option>
                                <option value="Alta">Alta</option>
                                <option value="Urgente">Urgente</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Responsável *
                            <?php if (count($this->data['users'] ?? [$_SESSION['user_id']]) == 1): ?>
                                <i class="fas fa-info-circle text-info" title="Você só pode atribuir para si mesmo. Gerentes podem atribuir para subordinados."></i>
                            <?php endif; ?>
                        </label>
                        <select name="responsible_user_id" class="form-select" required <?php echo count($this->data['users'] ?? [$_SESSION['user_id']]) == 1 ? 'readonly style="background-color: #e9ecef; pointer-events: none;"' : ''; ?>>
                            <?php if (!empty($this->data['users'])): ?>
                                <?php foreach ($this->data['users'] as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $_SESSION['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?> (Você)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?= $_SESSION['user_id'] ?>" selected><?= $_SESSION['user_name'] ?? 'Você' ?> (Você)</option>
                            <?php endif; ?>
                        </select>
                        <?php if (count($this->data['users'] ?? [$_SESSION['user_id']]) == 1): ?>
                            <div class="form-text text-muted">
                                <i class="fas fa-user me-1"></i>Apenas você pode ser o responsável por esta atividade.
                            </div>
                        <?php else: ?>
                            <div class="form-text text-success">
                                <i class="fas fa-users me-1"></i>Como gerente, você pode atribuir para qualquer membro da equipe.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data Agendada</label>
                            <input type="datetime-local" name="scheduled_date" id="scheduled_date_new" class="form-control">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>O sistema verificará conflitos de horário automaticamente
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duração (minutos)</label>
                            <input type="number" name="duration_minutes" id="duration_minutes_new" class="form-control" placeholder="Ex: 30" value="30">
                        </div>
                    </div>
                    
                    <!-- Alerta de conflito -->
                    <div id="conflict-alert-new" class="alert alert-warning d-none" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle fa-2x me-3 mt-1"></i>
                            <div>
                                <h6 class="alert-heading mb-2">⚠️ Conflito de Horário Detectado!</h6>
                                <div id="conflict-details-new"></div>
                                <hr>
                                <p class="mb-0"><small>Você pode continuar, mas a agenda ficará sobreposta.</small></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Parceiro</label>
                            <select name="partner_id" id="partner-select-new" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($this->data['partners'] ?? [] as $partner): ?>
                                    <option value="<?= $partner['id'] ?>">
                                        <?= htmlspecialchars($partner['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Oportunidade</label>
                            <select name="opportunity_id" id="opportunity-select-new" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($this->data['opportunities'] ?? [] as $opp): ?>
                                    <option value="<?= $opp['id'] ?>" data-partner-id="<?= $opp['partner_id'] ?? '' ?>">
                                        <?= htmlspecialchars($opp['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Salvar Atividade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modais de Edição para cada atividade -->
<?php foreach ($activities as $activity): ?>
<div class="modal fade" id="modalEditActivity<?= $activity['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Atividade
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-update-activity/<?= $activity['id'] ?>">
                <div class="modal-body">
                    <input type="hidden" name="redirect_to" value="crm-list-activities">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Atividade *</label>
                            <select name="type" class="form-select" required>
                                <option value="call" <?= $activity['type'] === 'call' ? 'selected' : '' ?>>📞 Ligação</option>
                                <option value="meeting" <?= $activity['type'] === 'meeting' ? 'selected' : '' ?>>👥 Reunião</option>
                                <option value="email" <?= $activity['type'] === 'email' ? 'selected' : '' ?>>✉️ E-mail</option>
                                <option value="task" <?= $activity['type'] === 'task' ? 'selected' : '' ?>>✔️ Tarefa</option>
                                <option value="note" <?= $activity['type'] === 'note' ? 'selected' : '' ?>>📝 Nota</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prioridade *</label>
                            <select name="priority" class="form-select" required>
                                <option value="Baixa" <?= $activity['priority'] === 'Baixa' ? 'selected' : '' ?>>Baixa</option>
                                <option value="Média" <?= $activity['priority'] === 'Média' ? 'selected' : '' ?>>Média</option>
                                <option value="Alta" <?= $activity['priority'] === 'Alta' ? 'selected' : '' ?>>Alta</option>
                                <option value="Urgente" <?= $activity['priority'] === 'Urgente' ? 'selected' : '' ?>>Urgente</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Responsável *
                            <?php if (count($this->data['users'] ?? [$_SESSION['user_id']]) == 1): ?>
                                <i class="fas fa-info-circle text-info" title="Você só pode atribuir para si mesmo. Gerentes podem atribuir para subordinados."></i>
                            <?php endif; ?>
                        </label>
                        <select name="responsible_user_id" class="form-select" required <?php echo count($this->data['users'] ?? [$_SESSION['user_id']]) == 1 ? 'readonly style="background-color: #e9ecef; pointer-events: none;"' : ''; ?>>
                            <?php if (!empty($this->data['users'])): ?>
                                <?php foreach ($this->data['users'] as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($activity['responsible_user_id'] ?? $_SESSION['user_id']) == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?> (Você)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?= $_SESSION['user_id'] ?>" selected><?= $_SESSION['user_name'] ?? 'Você' ?> (Você)</option>
                            <?php endif; ?>
                        </select>
                        <?php if (count($this->data['users'] ?? [$_SESSION['user_id']]) == 1): ?>
                            <div class="form-text text-muted">
                                <i class="fas fa-user me-1"></i>Apenas você pode ser o responsável por esta atividade.
                            </div>
                        <?php else: ?>
                            <div class="form-text text-success">
                                <i class="fas fa-users me-1"></i>Como gerente, você pode atribuir para qualquer membro da equipe.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($activity['title']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($activity['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data Agendada</label>
                            <input type="datetime-local" name="scheduled_date" class="form-control" 
                                   value="<?= $activity['scheduled_date'] ? date('Y-m-d\TH:i', strtotime($activity['scheduled_date'])) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duração (minutos)</label>
                            <input type="number" name="duration_minutes" class="form-control" 
                                   value="<?= $activity['duration_minutes'] ?? '' ?>" placeholder="Ex: 30">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Parceiro</label>
                            <select name="partner_id" id="partner-select-edit-<?= $activity['id'] ?>" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($this->data['partners'] ?? [] as $partner): ?>
                                    <option value="<?= $partner['id'] ?>" 
                                            <?= ($activity['partner_id'] ?? '') == $partner['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($partner['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Oportunidade</label>
                            <select name="opportunity_id" id="opportunity-select-edit-<?= $activity['id'] ?>" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach ($this->data['opportunities'] ?? [] as $opp): ?>
                                    <option value="<?= $opp['id'] ?>" 
                                            data-partner-id="<?= $opp['partner_id'] ?? '' ?>"
                                            <?= ($activity['opportunity_id'] ?? '') == $opp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opp['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="Pendente" <?= $activity['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Concluída" <?= $activity['status'] === 'Concluída' ? 'selected' : '' ?>>Concluída</option>
                            <option value="Cancelada" <?= $activity['status'] === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
// Filtrar oportunidades por parceiro (Nova Atividade e Edição)
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Inicializando filtros de atividades...');
    
    // Modal de NOVA atividade
    setupPartnerOpportunityFilter('partner-select-new', 'opportunity-select-new');
    
    // Modais de EDIÇÃO (para cada atividade)
    <?php foreach ($activities as $activity): ?>
    setupPartnerOpportunityFilter(
        'partner-select-edit-<?= $activity['id'] ?>', 
        'opportunity-select-edit-<?= $activity['id'] ?>'
    );
    <?php endforeach; ?>
    
    // ========================================
    // RESETAR MODAL DE NOVA ATIVIDADE AO FECHAR
    // ========================================
    const modalNewActivity = document.getElementById('modalNewActivity');
    if (modalNewActivity) {
        modalNewActivity.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                console.log('🔄 Modal de Nova Atividade resetado');
            }
        });
    }
    
    // Função reutilizável para filtrar oportunidades por parceiro
    function setupPartnerOpportunityFilter(partnerSelectId, opportunitySelectId) {
        const partnerSelect = document.getElementById(partnerSelectId);
        const opportunitySelect = document.getElementById(opportunitySelectId);
        
        if (!partnerSelect || !opportunitySelect) {
            console.log('⚠️ Elementos não encontrados:', partnerSelectId, opportunitySelectId);
            return;
        }
        
        console.log('✅ Configurando filtro para:', partnerSelectId);
        
        // Salvar todas as options originais
        const allOpportunities = Array.from(opportunitySelect.options);
        
        // Função para aplicar o filtro
        function applyFilter(selectedPartnerId) {
            console.log('📌 Aplicando filtro de parceiro:', selectedPartnerId, 'em', opportunitySelectId);
            
            // Salvar valor selecionado atual
            const currentOppValue = opportunitySelect.value;
            
            // Limpar select de oportunidades
            opportunitySelect.innerHTML = '<option value="">Selecione...</option>';
            
            if (!selectedPartnerId) {
                // Se nenhum parceiro selecionado, mostrar todas
                allOpportunities.forEach(opt => {
                    if (opt.value !== '') {
                        opportunitySelect.appendChild(opt.cloneNode(true));
                    }
                });
                console.log('ℹ️ Mostrando todas as oportunidades');
            } else {
                // Filtrar apenas oportunidades do parceiro selecionado
                let count = 0;
                allOpportunities.forEach(opt => {
                    const optPartnerId = opt.getAttribute('data-partner-id');
                    if (opt.value !== '' && optPartnerId === selectedPartnerId) {
                        opportunitySelect.appendChild(opt.cloneNode(true));
                        count++;
                    }
                });
                console.log('✅ Oportunidades filtradas:', count);
                
                if (count === 0) {
                    const noOppOption = document.createElement('option');
                    noOppOption.value = '';
                    noOppOption.textContent = 'Nenhuma oportunidade para este parceiro';
                    noOppOption.disabled = true;
                    opportunitySelect.appendChild(noOppOption);
                }
            }
            
            // Restaurar seleção se ainda existir nas options filtradas
            if (currentOppValue) {
                const stillExists = Array.from(opportunitySelect.options).find(opt => opt.value === currentOppValue);
                if (stillExists) {
                    opportunitySelect.value = currentOppValue;
                    console.log('✅ Oportunidade restaurada:', currentOppValue);
                }
            }
        }
        
        // Aplicar filtro ao mudar parceiro
        partnerSelect.addEventListener('change', function() {
            applyFilter(this.value);
        });
        
        // IMPORTANTE: Aplicar filtro IMEDIATAMENTE ao carregar (para modais de edição)
        const initialPartnerId = partnerSelect.value;
        if (initialPartnerId) {
            console.log('🔄 Aplicando filtro inicial para parceiro:', initialPartnerId);
            setTimeout(() => {
                applyFilter(initialPartnerId);
            }, 100);
        }
    }
    
    // ========================================
    // DETECÇÃO DE MUDANÇAS NÃO SALVAS
    // ========================================
    
    // Para cada modal de edição, detectar mudanças
    document.querySelectorAll('[id^="modalEditActivity"]').forEach(modal => {
        let formChanged = false;
        let originalFormData = {};
        
        // Ao abrir o modal, salvar estado original e resetar form
        modal.addEventListener('shown.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                // RESETAR o formulário para valores originais do servidor
                form.reset();
                
                formChanged = false;
                
                // Salvar estado original DEPOIS do reset
                setTimeout(() => {
                    originalFormData = new FormData(form);
                    console.log('📝 Formulário resetado e estado original salvo');
                }, 50);
                
                // Resetar cor do botão salvar
                const saveBtn = form.querySelector('button[type="submit"]');
                if (saveBtn) {
                    saveBtn.classList.remove('btn-warning');
                    saveBtn.classList.add('btn-primary');
                    saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';
                }
            }
        });
        
        // Detectar mudanças em qualquer campo
        modal.addEventListener('change', function(e) {
            if (e.target.form) {
                formChanged = true;
                console.log('⚠️ Formulário modificado (não salvo)');
                
                // Destacar botão salvar
                const saveBtn = e.target.form.querySelector('button[type="submit"]');
                if (saveBtn) {
                    saveBtn.classList.remove('btn-primary');
                    saveBtn.classList.add('btn-warning');
                    saveBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Salvar Alterações (Modificado)';
                }
            }
        });
        
        // Ao fechar modal SEM salvar, avisar se houve mudanças
        modal.addEventListener('hide.bs.modal', function(e) {
            if (formChanged) {
                const confirmClose = confirm('⚠️ Você fez alterações que não foram salvas!\n\nDeseja realmente fechar sem salvar?');
                if (!confirmClose) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🛑 Fechamento cancelado - Mudanças não salvas');
                }
            }
        });
        
        // Ao submeter o form, marcar como salvo
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                formChanged = false;
                console.log('✅ Formulário submetido - Salvando...');
            });
        }
    });
});
</script>


