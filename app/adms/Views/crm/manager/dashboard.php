<?php
$userStats = $this->data['user_stats'] ?? [];
$users = $this->data['users'] ?? [];
$filters = $this->data['filters'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-chart-line text-primary me-2"></i>
            Dashboard Gerencial
        </h1>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Colaborador</label>
                    <select name="user_id" class="form-select">
                        <option value="">Todos os Colaboradores</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filters['user_id'] == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" name="periodo_inicio" class="form-control" 
                           value="<?= $filters['periodo_inicio'] ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="periodo_fim" class="form-control" 
                           value="<?= $filters['periodo_fim'] ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($filters['user_id'])): ?>
        <!-- VISÃO GERAL DE TODOS OS COLABORADORES -->
        <div class="row">
            <?php foreach ($userStats as $userId => $stats): ?>
                <?php if (!empty($stats['name'])): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    <?= htmlspecialchars($stats['name']) ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Atividades -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Total de Atividades</span>
                                        <span class="badge bg-primary fs-6"><?= $stats['total_activities'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: <?= $stats['total_activities'] > 0 ? ($stats['completed_activities'] / $stats['total_activities'] * 100) : 0 ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        ✅ <?= $stats['completed_activities'] ?> concluídas | 
                                        ⏳ <?= $stats['pending_activities'] ?> pendentes |
                                        🔴 <?= $stats['overdue_activities'] ?> atrasadas
                                    </small>
                                </div>

                                <!-- Oportunidades -->
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Oportunidades Abertas</span>
                                        <span class="badge bg-warning"><?= $stats['total_opportunities'] ?></span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Valor Total</span>
                                        <span class="fw-bold text-success">R$ <?= number_format($stats['total_value'], 2, ',', '.') ?></span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Oportunidades Ganhas</span>
                                        <span class="badge bg-success"><?= $stats['won_opportunities'] ?></span>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Taxa de Conversão</span>
                                        <span class="fw-bold"><?= $stats['conversion_rate'] ?>%</span>
                                    </div>
                                </div>

                                <!-- Botão Ver Detalhes -->
                                <div class="mt-3">
                                    <a href="?user_id=<?= $userId ?>&periodo_inicio=<?= $filters['periodo_inicio'] ?>&periodo_fim=<?= $filters['periodo_fim'] ?>" 
                                       class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fas fa-eye me-1"></i>Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- VISÃO DETALHADA DE UM COLABORADOR -->
        <?php 
        $selectedUser = null;
        foreach ($users as $user) {
            if ($user['id'] == $filters['user_id']) {
                $selectedUser = $user;
                break;
            }
        }
        $stats = $userStats;
        ?>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    <?= htmlspecialchars($selectedUser['name'] ?? 'Colaborador') ?>
                </h4>
            </div>
            <div class="card-body">
                <!-- KPIs -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary mb-0"><?= $stats['total_activities'] ?></h3>
                                <small class="text-muted">Total Atividades</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success mb-0"><?= $stats['completed_activities'] ?></h3>
                                <small class="text-muted">Concluídas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning mb-0"><?= $stats['pending_activities'] ?></h3>
                                <small class="text-muted">Pendentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-danger mb-0"><?= $stats['overdue_activities'] ?></h3>
                                <small class="text-muted">Atrasadas</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Atividades por Tipo -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Atividades Recentes</h6>
                                <span class="badge bg-white text-info"><?= count($stats['recent_activities'] ?? []) ?></span>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (!empty($stats['recent_activities'])): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($stats['recent_activities'] as $activity): ?>
                                            <?php
                                            // Ícones por tipo
                                            $icons = [
                                                'call' => 'phone', 
                                                'meeting' => 'users', 
                                                'email' => 'envelope', 
                                                'task' => 'tasks',
                                                'note' => 'sticky-note'
                                            ];
                                            $icon = $icons[$activity['type']] ?? 'tasks';
                                            
                                            // Cores por tipo (iguais ao cadastro/dropdown)
                                            $typeColors = [
                                                'call' => '#e91e63',      // Rosa/Pink
                                                'meeting' => '#9c27b0',   // Roxo
                                                'email' => '#ba68c8',     // Roxo claro/Lilás
                                                'task' => '#6a1b9a',      // Roxo escuro
                                                'note' => '#ff9800'       // Laranja
                                            ];
                                            $typeColor = $typeColors[$activity['type']] ?? '#6c757d';
                                            
                                            // Cores por status
                                            $statusColors = [
                                                'Pendente' => 'warning',
                                                'Em Andamento' => 'info',
                                                'Concluída' => 'success',
                                                'Cancelada' => 'secondary'
                                            ];
                                            $statusColor = $statusColors[$activity['status']] ?? 'secondary';
                                            
                                            // Cores por prioridade
                                            $priorityColors = [
                                                'Alta' => 'danger',
                                                'Média' => 'warning',
                                                'Baixa' => 'info',
                                                'Urgente' => 'danger'
                                            ];
                                            $priorityColor = $priorityColors[$activity['priority']] ?? 'secondary';
                                            
                                            // Formatar data e hora
                                            $scheduledDate = $activity['scheduled_date'] ? new DateTime($activity['scheduled_date']) : null;
                                            $now = new DateTime();
                                            $isOverdue = $scheduledDate && $scheduledDate < $now && $activity['status'] !== 'Concluída';
                                            ?>
                                            <div class="list-group-item px-0 border-0 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-<?= $icon ?> me-1" style="color: <?= $typeColor ?>;"></i>
                                                            <a href="<?= $_ENV['URL_ADM'] ?>crm-view-activity/<?= $activity['id'] ?>" 
                                                               class="text-decoration-none">
                                                                <?= htmlspecialchars($activity['title']) ?>
                                                            </a>
                                                        </h6>
                                                        <?php if (!empty($activity['partner_name'])): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($activity['partner_name']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge bg-<?= $statusColor ?> ms-2">
                                                        <?= htmlspecialchars($activity['status']) ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?php if ($scheduledDate): ?>
                                                            <i class="fas fa-calendar-alt me-1"></i>
                                                            <?= $scheduledDate->format('d/m/Y') ?>
                                                            <i class="fas fa-clock ms-2 me-1"></i>
                                                            <?= $scheduledDate->format('H:i') ?>
                                                        <?php else: ?>
                                                            <i class="fas fa-calendar-times me-1"></i>
                                                            Sem data agendada
                                                        <?php endif; ?>
                                                    </small>
                                                    <small>
                                                        <span class="badge bg-<?= $priorityColor ?>" style="font-size: 0.7rem;">
                                                            <?= htmlspecialchars($activity['priority']) ?>
                                                        </span>
                                                        <?php if ($isOverdue): ?>
                                                            <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">
                                                                <i class="fas fa-exclamation-triangle"></i> ATRASADA
                                                            </span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Nenhuma atividade no período selecionado</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (count($stats['recent_activities'] ?? []) > 0): ?>
                                <div class="card-footer text-center">
                                    <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-list me-1"></i>Ver Todas as Atividades
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">Oportunidades</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Abertas</span>
                                        <strong><?= $stats['total_opportunities'] ?></strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Ganhas</span>
                                        <strong class="text-success"><?= $stats['won_opportunities'] ?></strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Valor Total</span>
                                        <strong class="text-success">R$ <?= number_format($stats['total_value'], 2, ',', '.') ?></strong>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <span>Taxa de Conversão</span>
                                        <strong><?= $stats['conversion_rate'] ?>%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

