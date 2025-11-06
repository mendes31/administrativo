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
            <?php 
            // Verificar permissão (seguindo padrão do projeto)
            $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
            $isCreator = $dashboard['created_by'] == ($_SESSION['user_id'] ?? 0);
            $canEdit = $isSuperAdmin || $isCreator;
            
            if ($canEdit):
            ?>
                <a href="<?= $_ENV['URL_ADM'] ?>edit-dashboard/<?= $dashboard['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button type="button" class="btn btn-outline-warning" onclick="duplicateDashboard(<?= $dashboard['id'] ?>)">
                    <i class="fas fa-copy"></i> Duplicar
                </button>
            <?php endif; ?>
            <a href="<?= $_ENV['URL_ADM'] ?>view-dynamic-report/<?= $dashboard['dynamic_report_id'] ?>" class="btn btn-info">
                <i class="fas fa-file-alt"></i> Ver Relatório Original
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>list-dashboards" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
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
                        <?php foreach ($filtersConfig as $index => $filter): ?>
                            <?php 
                                // Detectar tipo de filtro pelo label
                                $isYearFilter = (stripos($filter['label'], 'ano') !== false || stripos($filter['label'], 'year') !== false);
                                $isMonthFilter = (stripos($filter['label'], 'mês') !== false || stripos($filter['label'], 'mes') !== false || stripos($filter['label'], 'month') !== false);
                            ?>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">
                                    <?= htmlspecialchars($filter['label']) ?>
                                    <?php if (!empty($filter['required'])): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php if ($isYearFilter): ?>
                                    <!-- FILTRO ANO: Campo digitável -->
                                    <?php $defaultYear = $filter['default_value'] ?? date('Y'); ?>
                                    <input type="text" 
                                           name="filters[<?= htmlspecialchars($filter['field']) ?>]" 
                                           class="form-control" 
                                           placeholder="Digite o ano (ex: <?= date('Y') ?>)"
                                           value="<?= htmlspecialchars($defaultYear) ?>"
                                           pattern="\d{4}"
                                           title="Digite um ano válido (4 dígitos)"
                                           <?= !empty($filter['required']) ? 'required' : '' ?>>
                                    
                                <?php elseif ($isMonthFilter): ?>
                                    <!-- FILTRO MÊS: Lista fixa de 12 meses -->
                                    <select name="filters[<?= htmlspecialchars($filter['field']) ?>]" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="01">Janeiro</option>
                                        <option value="02">Fevereiro</option>
                                        <option value="03">Março</option>
                                        <option value="04">Abril</option>
                                        <option value="05">Maio</option>
                                        <option value="06">Junho</option>
                                        <option value="07">Julho</option>
                                        <option value="08">Agosto</option>
                                        <option value="09">Setembro</option>
                                        <option value="10">Outubro</option>
                                        <option value="11">Novembro</option>
                                        <option value="12">Dezembro</option>
                                    </select>
                                    
                                <?php else: ?>
                                    <!-- OUTROS FILTROS: Carregamento dinâmico via AJAX -->
                                    <select name="filters[<?= htmlspecialchars($filter['field']) ?>]" 
                                            class="form-select filter-dynamic" 
                                            data-field="<?= htmlspecialchars($filter['field']) ?>"
                                            id="filter_<?= $index ?>">
                                        <option value="">Carregando...</option>
                                    </select>
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

// Carregar opções de filtros dinâmicos
document.addEventListener('DOMContentLoaded', function() {
    const dynamicFilters = document.querySelectorAll('.filter-dynamic');
    
    dynamicFilters.forEach(async function(select) {
        const fieldName = select.dataset.field;
        
        try {
            const response = await fetch(`<?= $_ENV['URL_ADM'] ?>get-filter-options?dashboard_id=${dashboardId}&field=${encodeURIComponent(fieldName)}`);
            const result = await response.json();
            
            if (result.success) {
                select.innerHTML = '<option value="">Todos</option>';
                
                result.options.forEach(function(option) {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.textContent = option.label;
                    select.appendChild(opt);
                });
                
                console.log(`✅ ${result.count} opções carregadas para ${fieldName}`);
            } else {
                select.innerHTML = '<option value="">Erro ao carregar</option>';
                console.error('Erro:', result.error);
            }
        } catch (error) {
            select.innerHTML = '<option value="">Erro ao carregar</option>';
            console.error('Erro ao carregar opções:', error);
        }
    });
});

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

// Duplicar dashboard
function duplicateDashboard(dashboardId) {
    if (!confirm('Duplicar este dashboard?\n\nIsso criará uma cópia editável com todas as configurações atuais.')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $_ENV['URL_ADM'] ?>duplicate-dashboard';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'dashboard_id';
    input.value = dashboardId;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>
