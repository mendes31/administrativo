<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">People Analytics</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">People Analytics</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Cards de KPIs -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-primary shadow">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_employees'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Total de Colaboradores</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success shadow">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['active_employees'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Colaboradores Ativos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info shadow">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['turnover_rate'] ?? '0.00' ?>%</h3>
                    <p class="text-muted mb-0">Taxa de Rotatividade (12 meses)</p>
                    <small class="text-muted"><?= $this->data['terminated_last_year'] ?? 0 ?> desligamentos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4 mb-4">
        <!-- Gráfico de Headcount Mensal -->
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Headcount Mensal (12 meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="headcountChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Distribuição por Departamento -->
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribuição por Departamento</h6>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Turnover por Departamento -->
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Taxa de Turnover por Departamento</h6>
                </div>
                <div class="card-body">
                    <canvas id="turnoverChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Distribuição por Cargo -->
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Distribuição por Cargo</h6>
                </div>
                <div class="card-body">
                    <canvas id="positionChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-chart-bar me-2"></i>Análises e Indicadores</span>
        </div>
        <div class="card-body">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <h5>Métricas Calculadas:</h5>
                    <ul>
                        <li><strong>Taxa de Rotatividade:</strong> <?= $this->data['turnover_rate'] ?? '0.00' ?>% (últimos 12 meses)</li>
                        <li><strong>Tempo Médio de Permanência:</strong> <?= $this->data['avg_tenure'] ?? 'N/A' ?></li>
                        <li><strong>Total de Recontratações:</strong> <?= $this->data['total_rehires'] ?? 0 ?></li>
                        <li><strong>Headcount Atual:</strong> <?= $this->data['active_employees'] ?? 0 ?> colaboradores ativos</li>
                        <li><strong>Total de Colaboradores (histórico):</strong> <?= $this->data['total_employees'] ?? 0 ?></li>
                    </ul>
                    
                    <div class="mt-3">
                        <h6>Distribuição por Departamento:</h6>
                        <ul class="list-unstyled">
                            <?php foreach (($this->data['department_distribution'] ?? []) as $dept => $count): ?>
                                <li>
                                    <i class="fas fa-building text-primary me-2"></i>
                                    <?= htmlspecialchars($dept) ?>: <strong><?= $count ?></strong> colaboradores
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Relatórios Disponíveis:</h5>
                    <ul>
                        <li>Relatório de Headcount</li>
                        <li>Análise de Turnover</li>
                        <li>Dashboard de Engajamento</li>
                        <li>Métricas de Recrutamento</li>
                    </ul>
                    
                    <?php if (!empty($this->data['department_distribution'])): ?>
                        <div class="mt-3">
                            <h6>Distribuição por Departamento:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Departamento</th>
                                            <th class="text-center">Colaboradores</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->data['department_distribution'] as $dept => $count): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($dept) ?></td>
                                                <td class="text-center"><strong><?= $count ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (in_array('PeopleReports', $this->data['buttonPermission'] ?? [])) { ?>
                <div class="mt-3">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>people-reports" class="btn btn-primary">
                        <i class="fas fa-file-alt me-2"></i>Acessar Relatórios
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cores do tema
    const chartColors = {
        primary: '#0d6efd',
        success: '#198754',
        info: '#0dcaf0',
        warning: '#ffc107',
        danger: '#dc3545',
        purple: '#6f42c1',
        orange: '#fd7e14',
        pink: '#d63384',
        teal: '#20c997'
    };
    
    // ===== GRÁFICO DE HEADCOUNT MENSAL =====
    const headcountData = <?= json_encode($this->data['monthly_headcount'] ?? []) ?>;
    const headcountCtx = document.getElementById('headcountChart');
    if (headcountCtx) {
        new Chart(headcountCtx, {
            type: 'line',
            data: {
                labels: Object.keys(headcountData),
                datasets: [{
                    label: 'Colaboradores Ativos',
                    data: Object.values(headcountData),
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primary + '20',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Colaboradores: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // ===== GRÁFICO DE DISTRIBUIÇÃO POR DEPARTAMENTO =====
    const deptData = <?= json_encode($this->data['department_distribution'] ?? []) ?>;
    const deptCtx = document.getElementById('departmentChart');
    if (deptCtx && Object.keys(deptData).length > 0) {
        const colors = Object.keys(deptData).map((_, i) => {
            const colorArray = Object.values(chartColors);
            return colorArray[i % colorArray.length];
        });
        
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(deptData),
                datasets: [{
                    data: Object.values(deptData),
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // ===== GRÁFICO DE TURNOVER POR DEPARTAMENTO =====
    const turnoverData = <?= json_encode($this->data['turnover_by_department'] ?? []) ?>;
    const turnoverCtx = document.getElementById('turnoverChart');
    if (turnoverCtx && Object.keys(turnoverData).length > 0) {
        const deptLabels = Object.keys(turnoverData);
        const turnoverRates = deptLabels.map(dept => turnoverData[dept]['turnover_rate'] || 0);
        
        new Chart(turnoverCtx, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [{
                    label: 'Taxa de Turnover (%)',
                    data: turnoverRates,
                    backgroundColor: turnoverRates.map(rate => 
                        rate > 15 ? chartColors.danger : 
                        rate > 10 ? chartColors.warning : 
                        chartColors.success
                    ),
                    borderColor: turnoverRates.map(rate => 
                        rate > 15 ? chartColors.danger : 
                        rate > 10 ? chartColors.warning : 
                        chartColors.success
                    ),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Turnover: ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // ===== GRÁFICO DE DISTRIBUIÇÃO POR CARGO =====
    const positionData = <?= json_encode($this->data['position_distribution'] ?? []) ?>;
    const positionCtx = document.getElementById('positionChart');
    if (positionCtx && Object.keys(positionData).length > 0) {
        // Ordenar por quantidade (maior para menor) e pegar top 10
        const sortedPositions = Object.entries(positionData)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10);
        
        const posLabels = sortedPositions.map(([pos]) => pos);
        const posValues = sortedPositions.map(([, count]) => count);
        
        new Chart(positionCtx, {
            type: 'bar',
            data: {
                labels: posLabels,
                datasets: [{
                    label: 'Colaboradores',
                    data: posValues,
                    backgroundColor: chartColors.info,
                    borderColor: chartColors.info,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>

