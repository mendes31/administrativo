<?php
use App\adms\Helpers\FormatHelper;
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
        <h2 class="mb-0">
            <i class="fas fa-chart-line me-2"></i>Dashboard CRM
            <?php if (!$this->data['is_gestor']): ?>
                <small class="text-muted">(Meus Dados)</small>
            <?php endif; ?>
        </h2>
        <div class="btn-group">
            <a href="<?php echo $_ENV['URL_ADM']; ?>crm-kanban-pipeline" class="btn btn-success">
                <i class="fas fa-chart-bar me-2"></i>Ver Pipeline
            </a>
            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-file-pdf me-1"></i>Relatórios
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>crm-report-pipeline<?php echo isset($_GET['responsible_user_id']) ? '?responsible_user_id=' . $_GET['responsible_user_id'] : ''; ?>" target="_blank">
                    <i class="fas fa-file-pdf text-danger me-2"></i>Relatório do Pipeline
                </a></li>
                <li><a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>crm-report-performance" target="_blank">
                    <i class="fas fa-trophy text-warning me-2"></i>Performance por Vendedor
                </a></li>
                <li><a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>crm-report-conversion" target="_blank">
                    <i class="fas fa-chart-area text-info me-2"></i>Conversão do Funil
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Notificações/Alertas -->
    <?php if (!empty($this->data['pending_followups']) || !empty($this->data['overdue_activities'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Atenção!</h5>
        <?php if (!empty($this->data['overdue_activities'])): ?>
            <p class="mb-2">
                <strong><?= count($this->data['overdue_activities']) ?> atividade(s) atrasada(s)</strong>
                <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities?status=Pendente" class="btn btn-sm btn-warning ms-2">
                    Ver Atividades
                </a>
            </p>
        <?php endif; ?>
        <?php if (!empty($this->data['pending_followups'])): ?>
            <p class="mb-0">
                <strong><?= count($this->data['pending_followups']) ?> follow-up(s) pendente(s) para hoje</strong>
            </p>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Filtros (apenas para gestores) -->
    <?php if ($this->data['is_gestor']): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>crm-dashboard" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Período Início</label>
                    <input type="date" name="periodo_inicio" class="form-control" 
                           value="<?php echo htmlspecialchars($this->data['filters']['periodo_inicio'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Período Fim</label>
                    <input type="date" name="periodo_fim" class="form-control" 
                           value="<?php echo htmlspecialchars($this->data['filters']['periodo_fim'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Vendedor</label>
                    <select name="responsible_user_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['users'] ?? [] as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo ($this->data['filters']['responsible_user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-dashboard" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros Ativos (badges clicáveis) -->
    <div id="activeFilters" class="mb-3 d-none">
        <strong class="me-2">Filtros Ativos:</strong>
    </div>

    <!-- Cards de KPIs -->
    <div class="row mb-4">
        
        <?php if (!$this->data['is_gestor']): ?>
        <!-- Card exclusivo para Vendedores: Minhas Metas -->
        <div class="col-md-12 mb-3">
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Olá, <?php echo $_SESSION['user_name']; ?>!</h5>
                    <p class="mb-0">Este dashboard mostra apenas os seus dados de vendas e oportunidades.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Total de Parceiros -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Parceiros Ativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($this->data['total_partners'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de Leads -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Novos Leads
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php echo number_format($this->data['total_leads'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                                <div class="col">
                                    <small class="text-<?php echo $this->data['leads_change'] >= 0 ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-arrow-<?php echo $this->data['leads_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                                        <?php echo abs($this->data['leads_change']); ?>%
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Valor Total do Pipeline -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Valor do Pipeline
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ <?php echo number_format($this->data['total_pipeline_value'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Taxa de Conversão -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Taxa de Conversão
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php echo $this->data['conversion_rate']; ?>%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $this->data['conversion_rate']; ?>%" 
                                             aria-valuenow="<?php echo $this->data['conversion_rate']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos - Layout Otimizado -->
    <div class="row mb-4">
        <!-- Funil de Vendas -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card shadow h-100">
                <div class="card-header py-2 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-filter me-2"></i>Funil de Vendas
                    </h6>
                    <small class="text-white-50">Clique para filtrar por etapa</small>
                </div>
                <div class="card-body p-2">
                    <canvas id="funnelChart" height="180"></canvas>
                </div>
            </div>
        </div>

        <!-- Receita por Segmento -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card shadow h-100">
                <div class="card-header py-2 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-pie me-2"></i>Receita por Segmento
                    </h6>
                    <small class="text-white-50">Clique para filtrar por segmento</small>
                </div>
                <div class="card-body p-2">
                    <canvas id="segmentChart" height="180"></canvas>
                </div>
            </div>
        </div>

        <!-- Oportunidades por Mês -->
        <div class="col-lg-4 col-md-12 mb-3">
            <div class="card shadow h-100">
                <div class="card-header py-2" style="background-color: #2E9263; color: white;">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-line me-2"></i>Tendência Mensal
                    </h6>
                    <small style="opacity: 0.8;">Clique para filtrar por mês</small>
                </div>
                <div class="card-body p-2">
                    <canvas id="monthlyChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Parceiros - Layout Horizontal -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header py-2" style="background: linear-gradient(135deg, #2E9263 0%, #258556 100%); color: white;">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-trophy me-2"></i>Top 5 Parceiros por Receita
                    </h6>
                </div>
                <div class="card-body p-3">
                    <?php if (!empty($this->data['top_partners'])): ?>
                        <div class="row g-2">
                            <?php foreach ($this->data['top_partners'] as $index => $partner): ?>
                                <div class="col-md-<?php echo count($this->data['top_partners']) <= 3 ? '4' : (count($this->data['top_partners']) == 4 ? '3' : '2'); ?>">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body p-3 text-center">
                                            <div class="mb-2">
                                                <span class="badge rounded-circle bg-primary" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;">
                                                    <?php echo $index + 1; ?>
                                                </span>
                                            </div>
                                            <h6 class="mb-2" style="font-size: 0.9rem; min-height: 40px;">
                                                <?php echo htmlspecialchars($partner['name']); ?>
                                            </h6>
                                            <div class="h5 text-success mb-2">
                                                R$ <?php echo number_format($partner['total_revenue'] ?? 0, 0, ',', '.'); ?>
                                            </div>
                                            <div>
                                                <span class="badge bg-info me-1"><?php echo $partner['segment']; ?></span>
                                                <small class="text-muted"><?php echo $partner['total_opportunities']; ?> opp.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Nenhum dado disponível
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js"></script>
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/crm/dashboard-filters.js"></script>

<script>
// Tema verde Tiaraju para os gráficos
const tiarajuGreen = '#2E9263';
const chartColors = {
    primary: '#0d6efd',
    success: '#198754',
    info: '#0dcaf0',
    warning: '#ffc107',
    danger: '#dc3545',
    purple: '#6f42c1',
    orange: '#fd7e14',
    pink: '#d63384',
    teal: '#20c997',
    green: tiarajuGreen
};

// ===== FUNIL DE VENDAS =====
const funnelData = <?php echo json_encode($this->data['funnel_data']); ?>;
const funnelLabels = funnelData.map(item => item.name);
const funnelValues = funnelData.map(item => parseFloat(item.total_value || 0));
const funnelCounts = funnelData.map(item => parseInt(item.count || 0));
const funnelColors = funnelData.map(item => item.color);

// Verificar se há filtro de etapa ativo
const urlParams = new URLSearchParams(window.location.search);
const activeStage = urlParams.get('filter_stage');
const activeSegment = urlParams.get('filter_segment');

// Aplicar opacidade nas barras não selecionadas
const funnelBackgroundColors = funnelLabels.map((label, index) => {
    if (activeStage && activeStage !== label) {
        return funnelColors[index] + '40'; // 25% de opacidade (hex)
    }
    return funnelColors[index];
});

const funnelBorderColors = funnelLabels.map((label, index) => {
    if (activeStage && activeStage === label) {
        return '#000'; // Borda preta para destacar
    }
    return funnelColors[index];
});

const funnelBorderWidths = funnelLabels.map((label) => {
    return activeStage && activeStage === label ? 3 : 1;
});

const funnelChart = new Chart(document.getElementById('funnelChart'), {
    type: 'bar',
    data: {
        labels: funnelLabels,
        datasets: [{
            label: 'Valor (R$)',
            data: funnelValues,
            backgroundColor: funnelBackgroundColors,
            borderColor: funnelBorderColors,
            borderWidth: funnelBorderWidths
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + 
                               ' (' + funnelCounts[context.dataIndex] + ' oportunidades)';
                    }
                }
            }
        },
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

// Tornar gráfico clicável - Filtrar por Etapa
window.CrmDashboard.makeChartInteractive(funnelChart, 'filter_stage', function(chart, index) {
    return funnelLabels[index];
});

// ===== RECEITA POR SEGMENTO =====
const segmentData = <?php echo json_encode($this->data['revenue_by_segment']); ?>;
const segmentLabels = segmentData.map(item => item.segment);
const segmentValues = segmentData.map(item => parseFloat(item.total_revenue || 0));

// Cores base para cada segmento
const segmentBaseColors = {
    'Farma': chartColors.orange,
    'Suplementos': chartColors.purple,
    'Ambos': chartColors.green
};

// Aplicar opacidade nos segmentos não selecionados
const segmentBackgroundColors = segmentLabels.map(label => {
    const baseColor = segmentBaseColors[label] || chartColors.green;
    if (activeSegment && activeSegment !== label) {
        return baseColor + '40'; // 25% de opacidade
    }
    return baseColor;
});

const segmentBorderWidths = segmentLabels.map(label => {
    return activeSegment && activeSegment === label ? 4 : 2;
});

const segmentChart = new Chart(document.getElementById('segmentChart'), {
    type: 'doughnut',
    data: {
        labels: segmentLabels,
        datasets: [{
            data: segmentValues,
            backgroundColor: segmentBackgroundColors,
            borderColor: '#fff',
            borderWidth: segmentBorderWidths
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percent = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': R$ ' + context.parsed.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + 
                               ' (' + percent + '%)';
                    }
                }
            }
        }
    }
});

// Tornar gráfico clicável - Filtrar por Segmento
window.CrmDashboard.makeChartInteractive(segmentChart, 'filter_segment', function(chart, index) {
    return segmentLabels[index];
});

// ===== OPORTUNIDADES POR MÊS =====
const monthlyData = <?php echo json_encode($this->data['opportunities_by_month']); ?>;
const monthlyLabels = monthlyData.map(item => {
    const [year, month] = item.month.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
});
const monthlyValues = monthlyData.map(item => parseFloat(item.total_value || 0));
const monthlyCounts = monthlyData.map(item => parseInt(item.total || 0));

// Verificar se há período ativo
const activePeriodStart = urlParams.get('periodo_inicio');
const activePeriodEnd = urlParams.get('periodo_fim');

// Destacar ponto selecionado
const pointBackgroundColors = monthlyData.map(item => {
    if (activePeriodStart && item.month === activePeriodStart.substring(0, 7)) {
        return '#000'; // Preto para destaque
    }
    return tiarajuGreen;
});

const pointRadii = monthlyData.map(item => {
    if (activePeriodStart && item.month === activePeriodStart.substring(0, 7)) {
        return 10; // Maior
    }
    return 6;
});

const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Valor (R$)',
            data: monthlyValues,
            borderColor: tiarajuGreen,
            backgroundColor: tiarajuGreen + '20',
            tension: 0.4,
            fill: true,
            pointRadius: pointRadii,
            pointHoverRadius: 10,
            pointBackgroundColor: pointBackgroundColors,
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + 
                               ' (' + monthlyCounts[context.dataIndex] + ' oportunidades)';
                    }
                }
            }
        },
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

// Gráfico de linha: clicar em ponto para filtrar por mês
monthlyChart.options.onClick = function(event, activeElements) {
    if (activeElements.length > 0) {
        const index = activeElements[0].index;
        const monthData = <?php echo json_encode($this->data['opportunities_by_month']); ?>;
        const selectedMonth = monthData[index].month; // YYYY-MM
        
        // Converter para período início/fim
        const [year, month] = selectedMonth.split('-');
        const lastDay = new Date(year, month, 0).getDate();
        
        // Aplicar filtros de período
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        params.set('periodo_inicio', selectedMonth + '-01');
        params.set('periodo_fim', selectedMonth + '-' + lastDay);
        
        window.location.href = url.pathname + '?' + params.toString();
    }
};

monthlyChart.options.onHover = function(event, activeElements) {
    event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
};

monthlyChart.update();
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: .7rem;
}

.progress-sm {
    height: 0.5rem;
}

/* Indicador de gráficos clicáveis */
canvas {
    cursor: pointer !important;
    transition: all 0.2s ease;
}

canvas:hover {
    opacity: 0.9;
    transform: scale(1.01);
}

/* Cards dos gráficos com hover */
.card:has(canvas):hover {
    box-shadow: 0 .5rem 1rem rgba(46, 146, 99, .15) !important;
}

/* Filtros ativos */
#activeFilters {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #2E9263;
}

#activeFilters .badge {
    transition: all 0.2s ease;
}

#activeFilters .badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Animação ao aplicar filtro */
.filtering-animation {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255, 255, 255, 0.95);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    text-align: center;
}
</style>

