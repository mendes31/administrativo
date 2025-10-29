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
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Atividades por Tipo</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($stats['activities_by_type'])): ?>
                                    <?php foreach ($stats['activities_by_type'] as $typeData): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>
                                                <?php
                                                $icons = ['Ligação' => 'phone', 'Reunião' => 'users', 'E-mail' => 'envelope', 'Tarefa' => 'tasks'];
                                                $icon = $icons[$typeData['type']] ?? 'tasks';
                                                ?>
                                                <i class="fas fa-<?= $icon ?> me-2"></i><?= $typeData['type'] ?>
                                            </span>
                                            <span class="badge bg-secondary"><?= $typeData['total'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">Sem dados</p>
                                <?php endif; ?>
                            </div>
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

