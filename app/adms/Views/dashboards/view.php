<?php
$dashboard = $this->data['dashboard'] ?? [];
$kpisConfig = $dashboard['kpis_config'] ?? [];
$chartsConfig = $dashboard['charts_config'] ?? [];
$filtersConfig = $dashboard['filters_config'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mt-3">
            <i class="fas fa-chart-pie text-primary"></i> <?= htmlspecialchars($dashboard['name']) ?>
        </h2>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>list-dashboards" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>view-dynamic-report/<?= $dashboard['dynamic_report_id'] ?>" class="btn btn-info">
                <i class="fas fa-file-alt"></i> Ver Relatório Original
            </a>
        </div>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if (!empty($dashboard['description'])): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <?= nl2br(htmlspecialchars($dashboard['description'])) ?>
        </div>
    <?php endif; ?>

    <!-- Filtros Dinâmicos -->
    <?php if (!empty($filtersConfig)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
            </div>
            <div class="card-body">
                <form id="filtersForm">
                    <div class="row g-3">
                        <?php foreach ($filtersConfig as $filter): ?>
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= htmlspecialchars($filter['label']) ?></label>
                                <?php if ($filter['type'] === 'year'): ?>
                                    <select name="filters[<?= htmlspecialchars($filter['field']) ?>]" class="form-select">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                <?php elseif ($filter['type'] === 'month'): ?>
                                    <select name="filters[<?= htmlspecialchars($filter['field']) ?>]" class="form-select">
                                        <option value="">Todos os meses</option>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" name="filters[<?= htmlspecialchars($filter['field']) ?>]" 
                                           class="form-control" placeholder="Digite...">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-search"></i> Consultar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Loading -->
    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
        <p class="mt-3">Carregando dados...</p>
    </div>

    <!-- Conteúdo do Dashboard -->
    <div id="dashboardContent">
        <!-- KPIs -->
        <div class="row g-3 mb-4" id="kpisRow">
            <div class="col-12 text-center text-muted">
                <i class="fas fa-info-circle"></i> Clique em "Consultar" para carregar os dados
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row g-3 mb-4" id="chartsRow">
            <!-- Gráficos serão renderizados aqui -->
        </div>

        <!-- Info -->
        <div class="alert alert-info mt-3" id="dataInfo" style="display: none;"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dashboardId = <?= $dashboard['id'] ?>;
const kpisConfig = <?= json_encode($kpisConfig) ?>;
const chartsConfig = <?= json_encode($chartsConfig) ?>;
let chartInstances = {};

document.getElementById('filtersForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('dashboard_id', dashboardId);
    
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('dashboardContent').style.display = 'none';
    
    try {
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dashboard', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            updateDashboard(result);
        } else {
            alert('Erro: ' + result.error);
        }
        
    } catch (error) {
        alert('Erro ao carregar dados: ' + error.message);
    } finally {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('dashboardContent').style.display = 'block';
    }
});

function updateDashboard(result) {
    // Atualizar KPIs
    updateKPIs(result.kpis);
    
    // Atualizar Gráficos
    updateCharts(result.chart_data);
    
    // Info
    const info = document.getElementById('dataInfo');
    info.textContent = `${result.rows_count} registros processados em ${result.execution_time}s`;
    info.style.display = 'block';
}

function updateKPIs(kpis) {
    const row = document.getElementById('kpisRow');
    row.innerHTML = '';
    
    for (const [label, kpi] of Object.entries(kpis)) {
        const col = document.createElement('div');
        col.className = `col-md-${12 / Math.min(Object.keys(kpis).length, 4)}`;
        
        let formattedValue = kpi.value;
        if (kpi.format === 'currency') {
            formattedValue = 'R$ ' + parseFloat(kpi.value).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        } else if (kpi.format === 'percent') {
            formattedValue = parseFloat(kpi.value).toFixed(2) + '%';
        } else {
            formattedValue = parseInt(kpi.value).toLocaleString('pt-BR');
        }
        
        col.innerHTML = `
            <div class="card border-${kpi.color} shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">${label}</h6>
                            <h3 class="text-${kpi.color} mb-0">${formattedValue}</h3>
                        </div>
                        <div class="bg-${kpi.color} bg-opacity-10 p-3 rounded">
                            <i class="fas ${kpi.icon} fa-2x text-${kpi.color}"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
        row.appendChild(col);
    }
}

function updateCharts(chartData) {
    const row = document.getElementById('chartsRow');
    row.innerHTML = '';
    
    let chartIndex = 0;
    for (const [chartKey, data] of Object.entries(chartData)) {
        const config = chartsConfig[chartKey] || {};
        
        const col = document.createElement('div');
        col.className = 'col-md-6';
        
        const canvasId = 'chart_' + chartIndex;
        col.innerHTML = `
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">${config.title || 'Gráfico ' + (chartIndex + 1)}</h6>
                </div>
                <div class="card-body">
                    <canvas id="${canvasId}" height="200"></canvas>
                </div>
            </div>
        `;
        row.appendChild(col);
        
        // Renderizar gráfico
        setTimeout(() => {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            if (chartInstances[canvasId]) {
                chartInstances[canvasId].destroy();
            }
            
            chartInstances[canvasId] = new Chart(ctx, {
                type: config.type || 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        label: config.title,
                        data: Object.values(data),
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }, 100);
        
        chartIndex++;
    }
}
</script>
