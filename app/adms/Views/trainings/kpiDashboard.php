<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Dashboard de KPIs - Treinamentos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Treinamentos</li>
            <li class="breadcrumb-item">KPIs</li>
        </ol>
    </div>

    <?php $dashboard = $this->data['dashboard'] ?? []; ?>
    <?php $summary = $dashboard['summary'] ?? []; ?>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Vínculos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($summary['total'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-link fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Concluídos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($summary['concluidos'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($summary['pendentes'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Vencidos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($summary['vencidos'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <!-- Gráfico de Status (Pizza) -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Distribuição por Status
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Realizações Mensais (Barras) -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Realizações por Mês
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabelas de Dados -->
    <div class="row mb-4">
        <!-- Top Usuários com Pendências -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-2"></i>Top 5 - Usuários com Mais Pendências
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Colaborador</th>
                                    <th>Pendências</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $topPendingUsers = $dashboard['topPendingUsers'] ?? []; ?>
                                <?php if (!empty($topPendingUsers)): ?>
                                    <?php foreach ($topPendingUsers as $user): ?>
                                        <tr>
                                            <td><?= $user['user_id'] ?? $user['id'] ?? '-' ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($user['name']) ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $user['pendentes'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            Nenhum usuário com pendências encontrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Treinamentos Críticos -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle me-2"></i>Top 5 - Treinamentos Críticos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Treinamento</th>
                                    <th>Pendentes</th>
                                    <th>Vencidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $topCriticalTrainings = $dashboard['topCriticalTrainings'] ?? []; ?>
                                <?php if (!empty($topCriticalTrainings)): ?>
                                    <?php foreach ($topCriticalTrainings as $training): ?>
                                        <tr>
                                            <td><?= $training['training_id'] ?? $training['id'] ?? '-' ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($training['training_name']) ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $training['pendentes'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $training['vencidos'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Nenhum treinamento crítico encontrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas por Departamento -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building me-2"></i>Estatísticas por Departamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Departamento</th>
                                    <th>Total</th>
                                    <th>Concluídos</th>
                                    <th>Pendentes</th>
                                    <th>Vencidos</th>
                                    <th>% Conclusão</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $departmentStats = $dashboard['departmentStats'] ?? []; ?>
                                <?php if (!empty($departmentStats)): ?>
                                    <?php foreach ($departmentStats as $dept): ?>
                                        <?php 
                                        $total = $dept['total_vinculos'];
                                        $concluidos = $dept['concluidos'];
                                        $percentual = $total > 0 ? round(($concluidos / $total) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?= $dept['department_id'] ?? $dept['id'] ?? '-' ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($dept['department_name']) ?></td>
                                            <td><?= $total ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?= $concluidos ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $dept['pendentes'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $dept['vencidos'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: <?= $percentual ?>%"
                                                         aria-valuenow="<?= $percentual ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?= $percentual ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Nenhuma estatística por departamento encontrada.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Aplicações -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Últimas Aplicações
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Colaborador</th>
                                    <th>Treinamento</th>
                                    <th>Data Realização</th>
                                    <th>Data Agendada</th>
                                    <th>Status</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $recentApplications = $dashboard['recentApplications'] ?? []; ?>
                                <?php if (!empty($recentApplications)): ?>
                                    <?php foreach ($recentApplications as $app): ?>
                                        <tr>
                                            <td><?= $app['id'] ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($app['user_name']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($app['training_name']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($app['training_code']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($app['data_realizacao']): ?>
                                                    <?= FormatHelper::formatDate($app['data_realizacao']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($app['data_agendada']): ?>
                                                    <?= FormatHelper::formatDate($app['data_agendada']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($app['data_realizacao']): ?>
                                                    <span class="badge bg-success">Realizado</span>
                                                <?php elseif ($app['data_agendada']): ?>
                                                    <span class="badge bg-warning text-dark">Agendado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($app['nota']): ?>
                                                    <?= htmlspecialchars($app['nota']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            Nenhuma aplicação recente encontrada.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para os gráficos
    const statusData = <?= json_encode($dashboard['statusCounts'] ?? []) ?>;
    const monthlyData = <?= json_encode($dashboard['monthlyRealizations'] ?? []) ?>;

    // Gráfico de Status (Pizza)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Concluído', 'Pendente', 'Vencido', 'Próximo Vencimento', 'Em Dia'],
            datasets: [{
                data: [
                    statusData.concluido || 0,
                    statusData.pendente || 0,
                    statusData.vencido || 0,
                    statusData.proximo_vencimento || 0,
                    statusData.em_dia || 0
                ],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#fd7e14',
                    '#17a2b8'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Realizações Mensais (Barras)
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const months = Object.keys(monthlyData);
    const values = Object.values(monthlyData);
    
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: months.map(month => {
                const [year, monthNum] = month.split('-');
                const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                                  'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                return `${monthNames[parseInt(monthNum) - 1]}/${year.slice(2)}`;
            }),
            datasets: [{
                label: 'Realizações',
                data: values,
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script> 