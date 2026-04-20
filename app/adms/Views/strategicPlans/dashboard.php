<?php
$stats = $this->data['stats'] ?? [];
$periodStats = $this->data['periodStats'] ?? [];
$performanceIndicators = $this->data['performanceIndicators'] ?? [];
$costAnalysis = $this->data['costAnalysis'] ?? [];
$plansByStatus = $this->data['plansByStatus'] ?? [];
$plansByDepartment = $this->data['plansByDepartment'] ?? [];
$activePlans = $this->data['activePlans'] ?? [];
$upcomingDeadlines = $this->data['upcomingDeadlines'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Dashboard - Planejamento Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Planejamento Estratégico</li>
        </ol>
    </div>

    <!-- KPIs Principais -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-primary mb-1">Total de Planos</div>
                            <div class="h4 mb-0"><?= $stats['total_plans'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-project-diagram text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-success mb-1">Concluídos</div>
                            <div class="h4 mb-0"><?= $stats['completed'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-warning mb-1">Em Andamento</div>
                            <div class="h4 mb-0"><?= $stats['in_progress'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-play-circle text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-danger mb-1">Atrasados</div>
                            <div class="h4 mb-0"><?= $stats['delayed'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicadores de Performance -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <h5 class="card-title">Eficiência</h5>
                    <h3 class="text-success"><?= $performanceIndicators['efficiency'] ?? 0 ?>%</h3>
                    <small class="text-muted">Planos concluídos no prazo</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-tasks fa-2x"></i>
                    </div>
                    <h5 class="card-title">Taxa de Conclusão</h5>
                    <h3 class="text-info"><?= $performanceIndicators['completion_rate'] ?? 0 ?>%</h3>
                    <small class="text-muted">Planos finalizados</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <h5 class="card-title">Utilização do Orçamento</h5>
                    <h3 class="text-warning"><?= $performanceIndicators['budget_utilization'] ?? 0 ?>%</h3>
                    <small class="text-muted">Valor gasto vs planejado</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-percentage fa-2x"></i>
                    </div>
                    <h5 class="card-title">Progresso Médio</h5>
                    <h3 class="text-primary"><?= $performanceIndicators['avg_progress'] ?? 0 ?>%</h3>
                    <small class="text-muted">Média geral de progresso</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Gráfico de Status -->
        <div class="col-xl-6">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribuição por Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Comparação de Períodos -->
        <div class="col-xl-6">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Comparação de Períodos</h6>
                </div>
                <div class="card-body">
                    <canvas id="periodChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <!-- Análise de Custos por Departamento -->
        <div class="col-xl-6">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Custos por Departamento</h6>
                </div>
                <div class="card-body">
                    <canvas id="costChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Planos Ativos -->
        <div class="col-xl-6">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Planos Ativos</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($activePlans)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Plano</th>
                                        <th>Departamento</th>
                                        <th>Progresso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($activePlans, 0, 5) as $plan): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($plan['title']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($plan['responsible_name'] ?? 'Não informado') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($plan['department_name'] ?? 'Não informado') ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?= $plan['progress_percentage'] ?>%"
                                                         aria-valuenow="<?= $plan['progress_percentage'] ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?= $plan['progress_percentage'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum plano ativo encontrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <!-- Próximos Vencimentos -->
        <div class="col-12">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Próximos Vencimentos</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingDeadlines)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Plano</th>
                                        <th>Departamento</th>
                                        <th>Responsável</th>
                                        <th>Vencimento</th>
                                        <th>Progresso</th>
                                        <th>Dias Restantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingDeadlines as $plan): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($plan['title']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($plan['status']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($plan['department_name'] ?? 'Não informado') ?></td>
                                            <td><?= htmlspecialchars($plan['responsible_name'] ?? 'Não informado') ?></td>
                                            <td><?= date('d/m/Y', strtotime($plan['end_date'])) ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?= $plan['progress_percentage'] ?>%"
                                                         aria-valuenow="<?= $plan['progress_percentage'] ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?= $plan['progress_percentage'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $plan['days_remaining'] <= 7 ? 'bg-danger' : ($plan['days_remaining'] <= 30 ? 'bg-warning' : 'bg-success') ?>">
                                                    <?= $plan['days_remaining'] ?> dias
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-check fa-3x mb-3"></i>
                            <p>Nenhum vencimento próximo encontrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados dos gráficos
    const statusData = <?= json_encode($plansByStatus) ?>;
    const periodData = <?= json_encode($periodStats) ?>;
    const costData = <?= json_encode($costAnalysis) ?>;

    // Gráfico de Status (Pizza)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statusData.map(item => item.status),
            datasets: [{
                data: statusData.map(item => item.count),
                backgroundColor: [
                    '#ffc107', // Amarelo - Não iniciado
                    '#17a2b8', // Azul - Em andamento
                    '#28a745', // Verde - Concluído
                    '#dc3545'  // Vermelho - Atrasado
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Comparação de Períodos
    const periodCtx = document.getElementById('periodChart').getContext('2d');
    new Chart(periodCtx, {
        type: 'bar',
        data: {
            labels: ['Planos', 'Concluídos', 'Progresso (%)', 'Custo (R$)'],
            datasets: [{
                label: 'Período Atual',
                data: [
                    periodData.current?.total_plans || 0,
                    periodData.current?.completed || 0,
                    periodData.current?.avg_progress || 0,
                    periodData.current?.total_cost || 0
                ],
                backgroundColor: '#007bff'
            }, {
                label: 'Período Anterior',
                data: [
                    periodData.previous?.total_plans || 0,
                    periodData.previous?.completed || 0,
                    periodData.previous?.avg_progress || 0,
                    periodData.previous?.total_cost || 0
                ],
                backgroundColor: '#6c757d'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Custos por Departamento
    const costCtx = document.getElementById('costChart').getContext('2d');
    new Chart(costCtx, {
        type: 'bar',
        data: {
            labels: costData.map(item => item.department_name),
            datasets: [{
                label: 'Custo Total',
                data: costData.map(item => parseFloat(item.total_cost)),
                backgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });
});
</script>