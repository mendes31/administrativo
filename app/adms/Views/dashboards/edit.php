<?php
$dashboard = $this->data['dashboard'] ?? [];
$measuresConfig = $dashboard['measures_config'] ?? [];
$kpisConfig = $dashboard['kpis_config'] ?? [];
$chartsConfig = $dashboard['charts_config'] ?? [];
$filtersConfig = $dashboard['filters_config'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mt-3">
            <i class="fas fa-edit text-warning"></i> Editar Dashboard
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-dashboards">Meus Dashboards</a></li>
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>view-dashboard/<?= $dashboard['id'] ?>"><?= htmlspecialchars($dashboard['name']) ?></a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </nav>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Edição Avançada:</strong> Edite todas as configurações do dashboard usando as abas abaixo.
        <br><small>Para adicionar novos relatórios como fonte de dados, <a href="<?= $_ENV['URL_ADM'] ?>create-dashboard" class="alert-link">crie um novo dashboard</a>.</small>
    </div>

    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>edit-dashboard/update" id="dashboardForm">
        <input type="hidden" name="dashboard_id" value="<?= $dashboard['id'] ?>">
        
        <!-- Abas de Edição -->
        <ul class="nav nav-tabs mb-3" id="editTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basicInfo" type="button">
                    <i class="fas fa-info-circle"></i> Informações Básicas
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="measures-tab" data-bs-toggle="tab" data-bs-target="#measuresConfig" type="button">
                    <i class="fas fa-calculator"></i> Medidas <span class="badge bg-secondary" id="measuresCount"><?= count($measuresConfig) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="kpis-tab" data-bs-toggle="tab" data-bs-target="#kpisConfig" type="button">
                    <i class="fas fa-tachometer-alt"></i> KPIs <span class="badge bg-secondary" id="kpisCount"><?= count($kpisConfig) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="filters-tab" data-bs-toggle="tab" data-bs-target="#filtersConfig" type="button">
                    <i class="fas fa-filter"></i> Filtros <span class="badge bg-secondary" id="filtersCount"><?= count($filtersConfig) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="charts-tab" data-bs-toggle="tab" data-bs-target="#chartsConfig" type="button">
                    <i class="fas fa-chart-bar"></i> Gráficos <span class="badge bg-secondary" id="chartsCount"><?= count($chartsConfig) ?></span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="editTabsContent">
            
            <!-- ABA 1: Informações Básicas -->
            <div class="tab-pane fade show active" id="basicInfo" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações do Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nome do Dashboard *</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= htmlspecialchars($dashboard['name']) ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Categoria</label>
                                <input type="text" name="category" class="form-control" 
                                       value="<?= htmlspecialchars($dashboard['category'] ?? '') ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">Descrição</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($dashboard['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" class="form-check-input" id="is_public" 
                                           value="1" <?= $dashboard['is_public'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_public">
                                        Dashboard Público (visível para todos)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="alert alert-secondary">
                                    <i class="fas fa-database"></i>
                                    <strong>Relatório Base:</strong> <?= htmlspecialchars($dashboard['report_name']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ABA 2: Medidas Calculadas -->
            <div class="tab-pane fade" id="measuresConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-calculator"></i> Medidas Calculadas</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-lightbulb"></i> 
                            Clique em uma medida para editar ou use os botões para adicionar/remover.
                        </p>
                        
                        <div id="measuresContainer" class="mb-3">
                            <!-- Medidas serão renderizadas aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-success" id="addMeasureBtn">
                            <i class="fas fa-plus"></i> Adicionar Nova Medida
                        </button>
                        
                        <input type="hidden" name="measures_config" id="measuresConfig">
                    </div>
                </div>
            </div>
            
            <!-- ABA 3: KPIs -->
            <div class="tab-pane fade" id="kpisConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> KPIs</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-lightbulb"></i> 
                            Clique em um KPI para editar ou adicione novos.
                        </p>
                        
                        <div id="kpisContainer" class="mb-3">
                            <!-- KPIs serão renderizados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-success" id="addKpiBtn">
                            <i class="fas fa-plus"></i> Adicionar Novo KPI
                        </button>
                        
                        <input type="hidden" name="kpis_config" id="kpisConfig">
                    </div>
                </div>
            </div>
            
            <!-- ABA 4: Filtros -->
            <div class="tab-pane fade" id="filtersConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-lightbulb"></i> 
                            Clique em um filtro para editar ou adicione novos.
                        </p>
                        
                        <div id="filtersContainer" class="mb-3">
                            <!-- Filtros serão renderizados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-info" id="addFilterBtn">
                            <i class="fas fa-plus"></i> Adicionar Novo Filtro
                        </button>
                        
                        <input type="hidden" name="filters_config" id="filtersConfig">
                    </div>
                </div>
            </div>
            
            <!-- ABA 5: Gráficos -->
            <div class="tab-pane fade" id="chartsConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Gráficos</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-lightbulb"></i> 
                            Clique em um gráfico para editar ou adicione novos.
                        </p>
                        
                        <div id="chartsContainer" class="mb-3">
                            <!-- Gráficos serão renderizados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-warning" id="addChartBtn">
                            <i class="fas fa-plus"></i> Adicionar Novo Gráfico
                        </button>
                        
                        <input type="hidden" name="charts_config" id="chartsConfig">
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Botões de Ação -->
        <div class="d-flex gap-2 my-4">
            <button type="submit" class="btn btn-warning btn-lg">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
            <a href="<?= $_ENV['URL_ADM'] ?>view-dashboard/<?= $dashboard['id'] ?>" class="btn btn-secondary btn-lg">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script>
// Carregar configurações existentes
let measures = <?= json_encode($measuresConfig) ?>;
let kpis = <?= json_encode($kpisConfig) ?>;
let filters = <?= json_encode($filtersConfig) ?>;
let charts = <?= json_encode($chartsConfig) ?>;

console.log('📊 Configurações carregadas:', { measures, kpis, filters, charts });

// Renderizar configurações ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    updateMeasuresDisplay();
    updateKpisDisplay();
    updateFiltersDisplay();
    updateChartsDisplay();
});

// ========== MEDIDAS CALCULADAS ==========

function updateMeasuresDisplay() {
    const container = document.getElementById('measuresContainer');
    
    if (measures.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhuma medida calculada</p>';
    } else {
        container.innerHTML = '';
        measures.forEach((measure, index) => {
            const div = document.createElement('div');
            div.className = 'alert alert-secondary d-flex justify-content-between align-items-start mb-2';
            div.innerHTML = `
                <div style="flex: 1;">
                    <strong>${measure.name || 'Sem nome'}</strong>
                    <br><code class="text-muted">${measure.formula || 'Sem fórmula'}</code>
                    <br><small class="badge bg-info">${measure.format || 'number'}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="editMeasure(${index})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeMeasure(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    document.getElementById('measuresConfig').value = JSON.stringify(measures);
    document.getElementById('measuresCount').textContent = measures.length;
}

document.getElementById('addMeasureBtn')?.addEventListener('click', function() {
    const name = prompt('Nome da Medida (ex: Ticket Médio):');
    if (!name) return;
    
    const formula = prompt(
        'Fórmula (estilo Power BI/DAX):\n\n' +
        'Exemplos:\n' +
        '• [Total c/ Desc] / COUNT([NumDoc])\n' +
        '• CALCULATE(SUM([Campo]);[Filtro]="Valor")\n\n' +
        'Funções: SUM, AVG, COUNT, MIN, MAX, CALCULATE'
    );
    if (!formula) return;
    
    const format = prompt('Formato (number, currency, percent):', 'number');
    
    measures.push({ name, formula, format: format || 'number' });
    updateMeasuresDisplay();
});

function editMeasure(index) {
    const measure = measures[index];
    
    const name = prompt('Nome da Medida:', measure.name);
    if (name === null) return;
    
    const formula = prompt('Fórmula:', measure.formula);
    if (formula === null) return;
    
    const format = prompt('Formato (number, currency, percent):', measure.format);
    
    measures[index] = { 
        name: name || measure.name, 
        formula: formula || measure.formula, 
        format: format || measure.format 
    };
    updateMeasuresDisplay();
}

function removeMeasure(index) {
    if (confirm('Remover esta medida?')) {
        measures.splice(index, 1);
        updateMeasuresDisplay();
    }
}

// ========== KPIs ==========

function updateKpisDisplay() {
    const container = document.getElementById('kpisContainer');
    
    if (kpis.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhum KPI configurado</p>';
    } else {
        container.innerHTML = '';
        kpis.forEach((kpi, index) => {
            const div = document.createElement('div');
            div.className = 'alert alert-success d-flex justify-content-between align-items-start mb-2';
            div.innerHTML = `
                <div style="flex: 1;">
                    <strong>${kpi.label || 'Sem rótulo'}</strong>
                    <br><small>Campo: <code>${kpi.field || 'N/A'}</code></small>
                    <br><small class="badge bg-primary">${kpi.aggregation || 'N/A'}</small>
                    <small class="badge bg-secondary">${kpi.format || 'number'}</small>
                    <small class="badge bg-${kpi.color || 'primary'}">${kpi.icon || 'fa-chart-line'}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="editKpi(${index})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeKpi(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    document.getElementById('kpisConfig').value = JSON.stringify(kpis);
    document.getElementById('kpisCount').textContent = kpis.length;
}

document.getElementById('addKpiBtn')?.addEventListener('click', function() {
    const field = prompt('Campo (ex: Total c/ Desc, Ticket Médio):');
    if (!field) return;
    
    const label = prompt('Rótulo do KPI:', field);
    const aggregation = prompt('Agregação (sum, avg, count, count_distinct, min, max):', 'sum');
    const format = prompt('Formato (number, currency, percent):', 'currency');
    const icon = prompt('Ícone FontAwesome (ex: fa-dollar-sign):', 'fa-chart-line');
    const color = prompt('Cor (primary, success, danger, warning, info):', 'success');
    
    kpis.push({ field, label: label || field, aggregation: aggregation || 'sum', format: format || 'number', icon: icon || 'fa-chart-line', color: color || 'primary' });
    updateKpisDisplay();
});

function editKpi(index) {
    const kpi = kpis[index];
    
    const field = prompt('Campo:', kpi.field);
    if (field === null) return;
    
    const label = prompt('Rótulo:', kpi.label);
    const aggregation = prompt('Agregação:', kpi.aggregation);
    const format = prompt('Formato:', kpi.format);
    const icon = prompt('Ícone:', kpi.icon);
    const color = prompt('Cor:', kpi.color);
    
    kpis[index] = {
        field: field || kpi.field,
        label: label || kpi.label,
        aggregation: aggregation || kpi.aggregation,
        format: format || kpi.format,
        icon: icon || kpi.icon,
        color: color || kpi.color
    };
    updateKpisDisplay();
}

function removeKpi(index) {
    if (confirm('Remover este KPI?')) {
        kpis.splice(index, 1);
        updateKpisDisplay();
    }
}

// ========== FILTROS ==========

function updateFiltersDisplay() {
    const container = document.getElementById('filtersContainer');
    
    if (filters.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhum filtro configurado</p>';
    } else {
        container.innerHTML = '';
        filters.forEach((filter, index) => {
            const div = document.createElement('div');
            div.className = 'alert alert-info d-flex justify-content-between align-items-start mb-2';
            div.innerHTML = `
                <div style="flex: 1;">
                    <strong>${filter.label || 'Sem rótulo'}</strong>
                    <br><small>Campo: <code>${filter.field || 'N/A'}</code></small>
                    <br><small class="badge bg-primary">${filter.type || 'text'}</small>
                    ${filter.required ? '<small class="badge bg-danger">Obrigatório</small>' : ''}
                    ${filter.default_value ? '<small class="badge bg-secondary">Padrão: ' + filter.default_value + '</small>' : ''}
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="editFilter(${index})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFilter(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    document.getElementById('filtersConfig').value = JSON.stringify(filters);
    document.getElementById('filtersCount').textContent = filters.length;
}

document.getElementById('addFilterBtn')?.addEventListener('click', function() {
    const field = prompt('Campo (ex: nomeVendedor):');
    if (!field) return;
    
    const label = prompt('Rótulo do Filtro:', field);
    const type = prompt('Tipo (text, number, year, month):', 'text');
    const required = confirm('Este filtro é obrigatório?');
    const defaultValue = prompt('Valor padrão (deixe vazio se não houver):');
    
    filters.push({ 
        field, 
        label: label || field, 
        type: type || 'text',
        required: required,
        default_value: defaultValue || ''
    });
    updateFiltersDisplay();
});

function editFilter(index) {
    const filter = filters[index];
    
    const field = prompt('Campo:', filter.field);
    if (field === null) return;
    
    const label = prompt('Rótulo:', filter.label);
    const type = prompt('Tipo:', filter.type);
    const required = confirm('Obrigatório?');
    const defaultValue = prompt('Valor padrão:', filter.default_value);
    
    filters[index] = {
        field: field || filter.field,
        label: label || filter.label,
        type: type || filter.type,
        required: required,
        default_value: defaultValue || ''
    };
    updateFiltersDisplay();
}

function removeFilter(index) {
    if (confirm('Remover este filtro?')) {
        filters.splice(index, 1);
        updateFiltersDisplay();
    }
}

// ========== GRÁFICOS ==========

function updateChartsDisplay() {
    const container = document.getElementById('chartsContainer');
    
    if (Object.keys(charts).length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhum gráfico configurado</p>';
    } else {
        container.innerHTML = '';
        let index = 0;
        for (const [key, chart] of Object.entries(charts)) {
            const div = document.createElement('div');
            div.className = 'alert alert-warning d-flex justify-content-between align-items-start mb-2';
            div.innerHTML = `
                <div style="flex: 1;">
                    <strong>${chart.title || 'Gráfico ' + (index + 1)}</strong>
                    <br><small>Tipo: <code>${chart.type || 'bar'}</code> | Agrupar: <code>${chart.group_by || 'N/A'}</code></small>
                    <br><small class="badge bg-primary">${chart.value_field || 'N/A'}</small>
                    <small class="badge bg-secondary">${chart.aggregation || 'sum'}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-warning me-1" onclick="editChart('${key}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeChart('${key}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            index++;
        }
    }
    
    document.getElementById('chartsConfig').value = JSON.stringify(charts);
    document.getElementById('chartsCount').textContent = Object.keys(charts).length;
}

document.getElementById('addChartBtn')?.addEventListener('click', function() {
    const type = prompt('Tipo (bar, line, pie, doughnut):', 'bar');
    if (!type) return;
    
    const groupBy = prompt('Agrupar por (campo):');
    if (!groupBy) return;
    
    const valueField = prompt('Campo de Valor:');
    if (!valueField) return;
    
    const aggregation = prompt('Agregação (sum, avg, count):', 'sum');
    const title = prompt('Título do Gráfico:');
    
    const chartKey = 'chart' + (Object.keys(charts).length + 1);
    charts[chartKey] = {
        type: type || 'bar',
        group_by: groupBy,
        value_field: valueField,
        aggregation: aggregation || 'sum',
        title: title || 'Gráfico ' + (Object.keys(charts).length + 1)
    };
    updateChartsDisplay();
});

function editChart(key) {
    const chart = charts[key];
    
    const type = prompt('Tipo:', chart.type);
    if (type === null) return;
    
    const groupBy = prompt('Agrupar por:', chart.group_by);
    const valueField = prompt('Campo de Valor:', chart.value_field);
    const aggregation = prompt('Agregação:', chart.aggregation);
    const title = prompt('Título:', chart.title);
    
    charts[key] = {
        type: type || chart.type,
        group_by: groupBy || chart.group_by,
        value_field: valueField || chart.value_field,
        aggregation: aggregation || chart.aggregation,
        title: title || chart.title
    };
    updateChartsDisplay();
}

function removeChart(key) {
    if (confirm('Remover este gráfico?')) {
        delete charts[key];
        updateChartsDisplay();
    }
}
</script>
