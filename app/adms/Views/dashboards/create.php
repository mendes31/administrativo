<?php
$report = $this->data['report'] ?? null;
$reports = $this->data['reports'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mt-3">
            <i class="fas fa-chart-pie text-success"></i> Criar Dashboard
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-dashboards">Meus Dashboards</a></li>
                <li class="breadcrumb-item active">Criar</li>
            </ol>
        </nav>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>create-dashboard" id="dashboardForm">
        <!-- ETAPA 1: Seleção de Relatórios -->
        <div class="card shadow-sm mb-4" id="step1">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <span class="badge bg-light text-primary me-2">1</span>
                    <i class="fas fa-file-alt"></i> Selecionar Relatórios Base
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Primeiro passo:</strong> Selecione um ou mais relatórios que deseja usar neste dashboard.
                    Os campos desses relatórios estarão disponíveis para criar KPIs e gráficos.
                </div>
                
                <div class="row g-3">
                    <?php foreach ($reports as $r): ?>
                        <div class="col-md-4">
                            <div class="card border-secondary h-100 report-card">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input report-checkbox" 
                                               type="checkbox" 
                                               name="selected_reports[]" 
                                               value="<?= $r['id'] ?>"
                                               id="report_<?= $r['id'] ?>"
                                               data-sql="<?= htmlspecialchars($r['custom_sql'] ?? '') ?>"
                                               <?= $report && $r['id'] == $report['id'] ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="report_<?= $r['id'] ?>">
                                            <i class="fas fa-file-alt text-primary"></i>
                                            <?= htmlspecialchars($r['name']) ?>
                                        </label>
                                    </div>
                                    <?php if ($r['category']): ?>
                                        <span class="badge bg-secondary mt-2"><?= htmlspecialchars($r['category']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($r['description']): ?>
                                        <p class="small text-muted mt-2 mb-0"><?= htmlspecialchars(substr($r['description'], 0, 100)) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-center">
                    <button type="button" class="btn btn-primary btn-lg" id="btnNextStep">
                        Próximo: Configurar Dashboard <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- ETAPA 2: Configuração do Dashboard -->
        <div id="step2" style="display: none;">
            <!-- Informações Básicas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <span class="badge bg-light text-success me-2">2</span>
                        <i class="fas fa-info-circle"></i> Informações do Dashboard
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome do Dashboard *</label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="Ex: Dashboard de Vendas 2025"
                                   value="<?= $report ? 'Dashboard - ' . htmlspecialchars($report['name']) : '' ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoria</label>
                            <input type="text" name="category" class="form-control" 
                                   placeholder="Ex: Vendas, Financeiro, Estoque"
                                   value="<?= htmlspecialchars($report['category'] ?? '') ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Descrição</label>
                            <textarea name="description" class="form-control" rows="2" 
                                      placeholder="Descreva o objetivo deste dashboard..."><?= htmlspecialchars($report['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Layout</label>
                            <select name="layout" class="form-select">
                                <option value="default">Padrão (3 colunas)</option>
                                <option value="compact">Compacto (4 colunas)</option>
                                <option value="full">Completo (KPIs + Gráficos)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_public" id="isPublic">
                                <label class="form-check-label" for="isPublic">
                                    <i class="fas fa-globe"></i> Dashboard público
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" id="btnBackStep">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </button>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="dynamic_report_id" id="primaryReportId" value="<?= $report['id'] ?? '' ?>">

        <!-- Layout de Construção (estilo Power BI) -->
        <div class="row mb-4">
            <!-- Painel de Campos (Direita - estilo Power BI) -->
            <div class="col-md-3 order-md-2">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-database"></i> Campos Disponíveis
                        </h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <div id="fieldsPanel">
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-info-circle"></i>
                                <br>
                                Selecione relatórios na etapa 1
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Área de Configuração (Esquerda) -->
            <div class="col-md-9 order-md-1">
                <!-- Medidas Calculadas -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-purple text-white" style="background-color: #6f42c1 !important;">
                        <h5 class="mb-0"><i class="fas fa-calculator"></i> Medidas Calculadas</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-lightbulb"></i> 
                            Crie medidas customizadas usando fórmulas (estilo Power BI DAX)
                        </p>
                        
                        <div id="measuresContainer" class="border rounded p-3 mb-3" style="min-height: 80px; background: #f8f9fa;">
                            <!-- Medidas serão adicionadas aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-outline-purple btn-sm" id="addMeasureBtn" style="border-color: #6f42c1; color: #6f42c1;">
                            <i class="fas fa-plus"></i> Nova Medida
                        </button>
                        
                        <div class="alert alert-info mt-3 mb-0" style="font-size: 0.85rem;">
                            <strong>Funções disponíveis:</strong>
                            <code>SUM([campo])</code>, 
                            <code>AVG([campo])</code>, 
                            <code>COUNT([campo])</code>, 
                            <code>COUNT_DISTINCT([campo])</code>, 
                            <code>MIN([campo])</code>, 
                            <code>MAX([campo])</code>
                            <br>
                            <strong>Operadores:</strong> <code>+</code>, <code>-</code>, <code>*</code>, <code>/</code>, <code>()</code>
                            <br>
                            <strong>Exemplo:</strong> <code>[DescontoRodape] / [TotalLinha] * 100</code>
                        </div>
                        
                        <input type="hidden" name="measures_config" id="measuresConfig" value="[]">
                    </div>
                </div>
                
                <!-- Configuração de KPIs -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Configurar KPIs</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-hand-point-right"></i> 
                            Arraste campos ou medidas calculadas para criar KPIs
                        </p>
                        
                        <div id="kpisContainer" class="border rounded p-3 mb-3 config-container" style="min-height: 150px; background: #f8f9fa;">
                            <!-- KPIs serão adicionados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-outline-success btn-sm" id="addKpiBtn">
                            <i class="fas fa-plus"></i> Adicionar KPI
                        </button>
                        
                        <input type="hidden" name="kpis_config" id="kpisConfig" value="[]">
                    </div>
                </div>

        <!-- Configuração de Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Configurar Filtros</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Configure quais filtros estarão disponíveis no dashboard:</p>
                
                <div id="filtersContainer" class="border rounded p-3 mb-3 config-container" style="min-height: 150px; background: #f8f9fa;">
                    <!-- Filtros serão adicionados aqui -->
                </div>
                
                <button type="button" class="btn btn-outline-info btn-sm" id="addFilterBtn">
                    <i class="fas fa-plus"></i> Adicionar Filtro
                </button>
                
                <input type="hidden" name="filters_config" id="filtersConfig" value="[]">
            </div>
        </div>
            </div>
        </div>

                <!-- Configuração de Gráficos -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Configurar Gráficos</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-hand-point-right"></i> 
                            Arraste campos do painel direito ou clique em "Adicionar Gráfico"
                        </p>
                        
                        <div id="chartsContainer" class="border rounded p-3 mb-3 config-container" style="min-height: 150px; background: #f8f9fa;">
                            <!-- Gráficos serão adicionados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-outline-warning btn-sm" id="addChartBtn">
                            <i class="fas fa-plus"></i> Adicionar Gráfico
                        </button>
                        
                        <input type="hidden" name="charts_config" id="chartsConfig" value="[]">
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-flex gap-2 mb-4">
                    <button type="button" class="btn btn-secondary" id="btnBackStep2">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save"></i> Criar Dashboard
                    </button>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-dashboards" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>
        </div>
    </form>
</div>

<script>
let kpis = [];
let filters = [];
let charts = [];
let measures = [];
let selectedReports = [];
let availableFields = {};

// Controle de Etapas
document.getElementById('btnNextStep')?.addEventListener('click', function() {
    const checked = document.querySelectorAll('.report-checkbox:checked');
    
    if (checked.length === 0) {
        alert('❌ Selecione pelo menos 1 relatório!');
        return;
    }
    
    // Salvar relatórios selecionados
    selectedReports = Array.from(checked).map(cb => ({
        id: cb.value,
        name: cb.closest('.card-body').querySelector('label').textContent.trim(),
        sql: cb.dataset.sql
    }));
    
    // Definir o primeiro como primário
    document.getElementById('primaryReportId').value = selectedReports[0].id;
    
    // Carregar campos dos relatórios selecionados
    loadFieldsFromReports();
    
    // Trocar etapa
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    
    // Inicializar event listeners da etapa 2
    initStep2Events();
    
    // Scroll para o topo
    window.scrollTo(0, 0);
});

// Inicializar eventos da etapa 2
function initStep2Events() {
    console.log('🎯 Inicializando eventos da etapa 2...');
    
    // Botão Adicionar KPI
    const addKpiBtn = document.getElementById('addKpiBtn');
    if (addKpiBtn) {
        addKpiBtn.onclick = function() {
            const field = prompt('Nome do campo (ex: TotalLinha, Qtde):');
            if (!field) return;
            
            const label = prompt('Rótulo do KPI (ex: Faturamento Total):') || field;
            const aggregation = prompt('Agregação (sum, avg, count, count_distinct, min, max):', 'sum');
            const format = prompt('Formato (number, currency, percent):', 'currency');
            const icon = prompt('Ícone FontAwesome (ex: fa-dollar-sign):', 'fa-chart-line');
            const color = prompt('Cor (primary, success, danger, warning, info):', 'success');
            
            kpis.push({ field, label, aggregation, format, icon, color });
            updateKpisDisplay();
        };
        console.log('✅ Evento addKpiBtn adicionado');
    }
    
    // Botão Adicionar Filtro
    const addFilterBtn = document.getElementById('addFilterBtn');
    if (addFilterBtn) {
        addFilterBtn.onclick = function() {
            addFilter();
        };
        console.log('✅ Evento addFilterBtn adicionado');
    }
    
    // Botão Adicionar Gráfico
    const addChartBtn = document.getElementById('addChartBtn');
    if (addChartBtn) {
        addChartBtn.onclick = function() {
            addChart();
        };
        console.log('✅ Evento addChartBtn adicionado');
    }
    
    // Botão Adicionar Medida
    const addMeasureBtn = document.getElementById('addMeasureBtn');
    if (addMeasureBtn) {
        addMeasureBtn.onclick = function() {
            addMeasure();
        };
        console.log('✅ Evento addMeasureBtn adicionado');
    }
    
    console.log('✅ Todos os eventos da etapa 2 inicializados!');
}

// Adicionar Medida Calculada
function addMeasure() {
    const name = prompt('Nome da Medida (ex: % Desconto, Ticket Médio):');
    if (!name) return;
    
    const formula = prompt(
        'Fórmula (estilo Power BI/DAX):\n\n' +
        'Exemplos:\n' +
        '• [Total Desconto] / [TotalLinha] * 100\n' +
        '• CALCULATE([Total Desconto] / CALCULATE(SUM(fRM_VENDAS[Total s/ Desc]);fRM_VENDAS[Tipo Saida]="Venda"))\n' +
        '• SUM([Campo])\n' +
        '• CALCULATE(SUM([Campo]);[Filtro]="Valor")\n\n' +
        'Funções: SUM, AVG, COUNT, MIN, MAX, CALCULATE\n' +
        'Referências: [Campo] ou TABELA[Campo]\n' +
        'Operadores: + - * / ()\n' +
        'Filtros: TABELA[Campo]="Valor" ou [Campo]="Valor"'
    );
    if (!formula) return;
    
    const format = prompt('Formato de exibição (number, currency, percent):', 'number');
    
    // Validar fórmula básica
    if (!validateFormula(formula)) {
        alert('❌ Fórmula inválida! Use apenas funções permitidas e operadores matemáticos.');
        return;
    }
    
    measures.push({ name, formula, format });
    updateMeasuresDisplay();
    updateFieldsPanel();
    
    console.log('✅ Medida criada:', name, formula);
}

function validateFormula(formula) {
    // Validação mais permissiva: aceitar caracteres alfanuméricos, espaços, pontuação comum
    // Permite: letras, números, espaços, colchetes, parênteses, operadores, aspas, pontos, vírgulas, barras, underscores, acentos
    // Bloqueia apenas: < > & | $ ` \ (caracteres potencialmente perigosos)
    const dangerous = /[<>&|$`\\]/;
    if (dangerous.test(formula)) {
        console.warn('⚠️ Caracteres perigosos detectados na fórmula');
        return false;
    }
    
    // Verificar se tem conteúdo
    if (!formula || formula.trim().length === 0) {
        console.warn('⚠️ Fórmula vazia');
        return false;
    }
    
    // Verificar funções permitidas (incluindo CALCULATE)
    const allowedFunctions = ['SUM', 'AVG', 'COUNT', 'COUNT_DISTINCT', 'MIN', 'MAX', 'CALCULATE'];
    const funcRegex = /([A-Z_]+)\s*\(/gi;
    let match;
    const foundFunctions = [];
    
    while ((match = funcRegex.exec(formula)) !== null) {
        const funcName = match[1].toUpperCase();
        if (!allowedFunctions.includes(funcName)) {
            console.warn('⚠️ Função não permitida:', match[1]);
            alert('⚠️ Função não permitida: ' + match[1] + '\n\nFunções permitidas: ' + allowedFunctions.join(', '));
            return false;
        }
        foundFunctions.push(funcName);
    }
    
    // Validar estrutura básica de CALCULATE
    if (formula.includes('CALCULATE') || formula.includes('calculate')) {
        const closingMatches = (formula.match(/\)/g) || []).length;
        const openingMatches = (formula.match(/\(/g) || []).length;
        
        if (openingMatches !== closingMatches) {
            console.warn('⚠️ Parênteses não balanceados na fórmula');
            console.warn('   Abertos: ' + openingMatches + ', Fechados: ' + closingMatches);
            alert('⚠️ Parênteses não balanceados!\n\nAbertos: ' + openingMatches + '\nFechados: ' + closingMatches);
            return false;
        }
    }
    
    // Validar colchetes balanceados
    const openBrackets = (formula.match(/\[/g) || []).length;
    const closeBrackets = (formula.match(/\]/g) || []).length;
    
    if (openBrackets !== closeBrackets) {
        console.warn('⚠️ Colchetes não balanceados na fórmula');
        console.warn('   Abertos: ' + openBrackets + ', Fechados: ' + closeBrackets);
        alert('⚠️ Colchetes não balanceados!\n\nAbertos: ' + openBrackets + '\nFechados: ' + closeBrackets);
        return false;
    }
    
    console.log('✅ Fórmula validada com sucesso:', formula);
    return true;
}

function updateMeasuresDisplay() {
    const container = document.getElementById('measuresContainer');
    
    if (measures.length === 0) {
        container.innerHTML = '<p class="text-muted text-center m-0"><small><i class="fas fa-info-circle"></i> Nenhuma medida criada</small></p>';
        document.getElementById('measuresConfig').value = '[]';
        return;
    }
    
    container.innerHTML = '';
    
    measures.forEach((measure, index) => {
        const div = document.createElement('div');
        div.className = 'alert alert-purple mb-2 d-flex justify-content-between align-items-start';
        div.style.backgroundColor = '#e7d9ff';
        div.style.borderColor = '#6f42c1';
        div.innerHTML = `
            <div style="flex: 1;">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-calculator text-purple me-2" style="color: #6f42c1;"></i>
                    <strong style="color: #6f42c1;">${measure.name}</strong>
                    <span class="badge bg-secondary ms-2">${measure.format}</span>
                </div>
                <code style="font-size: 0.85rem; color: #495057;">${measure.formula}</code>
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeMeasure(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('measuresConfig').value = JSON.stringify(measures);
}

function removeMeasure(index) {
    measures.splice(index, 1);
    updateMeasuresDisplay();
    updateFieldsPanel();
}

// Atualizar painel de campos para incluir medidas
function updateFieldsPanel() {
    // Adicionar seção de medidas no painel
    const panel = document.getElementById('fieldsPanel');
    const accordion = panel.querySelector('.accordion');
    
    if (!accordion) return;
    
    // Remover seção de medidas antiga se existir
    const oldMeasures = document.getElementById('measures_section');
    if (oldMeasures) {
        oldMeasures.remove();
    }
    
    if (measures.length === 0) return;
    
    // Criar nova seção de medidas
    let measuresHtml = `
        <div class="accordion-item" id="measures_section">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#fields_measures"
                        style="background-color: #e7d9ff; color: #6f42c1;">
                    <i class="fas fa-calculator text-purple me-2" style="color: #6f42c1;"></i>
                    <strong>📐 Medidas Calculadas</strong>
                    <span class="badge bg-purple ms-2" style="background-color: #6f42c1 !important;">${measures.length}</span>
                </button>
            </h2>
            <div id="fields_measures" class="accordion-collapse collapse show">
                <div class="accordion-body p-0">
                    <div class="list-group list-group-flush">
    `;
    
    measures.forEach(measure => {
        measuresHtml += `
            <div class="list-group-item list-group-item-action p-2 field-item" 
                 draggable="true"
                 data-field="${measure.name}"
                 data-measure="true"
                 data-formula="${measure.formula}"
                 style="cursor: grab; background-color: #f8f4ff;">
                <i class="fas fa-grip-vertical text-muted me-2"></i>
                <i class="fas fa-calculator text-purple me-1" style="color: #6f42c1;"></i>
                <small><strong>${measure.name}</strong></small>
                <br>
                <small class="text-muted" style="font-size: 0.75rem; margin-left: 2rem;">${measure.formula}</small>
            </div>
        `;
    });
    
    measuresHtml += `
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar no topo do accordion
    accordion.insertAdjacentHTML('afterbegin', measuresHtml);
    
    // Re-inicializar drag and drop
    setTimeout(() => {
        initDragAndDrop();
    }, 100);
}

document.getElementById('btnBackStep')?.addEventListener('click', function() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    window.scrollTo(0, 0);
});

document.getElementById('btnBackStep2')?.addEventListener('click', function() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    window.scrollTo(0, 0);
});

// Carregar campos dos relatórios selecionados
async function loadFieldsFromReports() {
    const panel = document.getElementById('fieldsPanel');
    panel.innerHTML = '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm"></div> Carregando campos...</div>';
    
    // Buscar campos reais executando as queries
    const promises = selectedReports.map(report => getFieldsFromReport(report.id));
    
    try {
        const results = await Promise.all(promises);
        
        let html = '';
        
        selectedReports.forEach((report, index) => {
            const fields = results[index] || [];
            availableFields[report.id] = fields;
            
            console.log(`📊 Relatório "${report.name}": ${fields.length} campos encontrados`);
            
            html += `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#fields_${report.id}">
                            <i class="fas fa-table text-primary me-2"></i>
                            <strong>${report.name}</strong>
                            <span class="badge bg-primary ms-2">${fields.length}</span>
                        </button>
                    </h2>
                    <div id="fields_${report.id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}">
                        <div class="accordion-body p-0">
                            <div class="list-group list-group-flush">
            `;
            
            fields.forEach(field => {
                html += `
                    <div class="list-group-item list-group-item-action p-2 field-item" 
                         draggable="true"
                         data-field="${field}"
                         data-report="${report.id}"
                         style="cursor: grab;">
                        <i class="fas fa-grip-vertical text-muted me-2"></i>
                        <i class="fas fa-database text-success me-1"></i>
                        <small><strong>${field}</strong></small>
                    </div>
                `;
            });
            
            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        panel.innerHTML = '<div class="accordion accordion-flush">' + html + '</div>';
        
        console.log(`✅ Painel de campos carregado com ${selectedReports.length} relatórios`);
        
        // Adicionar eventos de drag após renderizar
        setTimeout(() => {
            initDragAndDrop();
        }, 100);
        
    } catch (error) {
        console.error('❌ Erro ao carregar campos:', error);
        panel.innerHTML = '<div class="alert alert-danger m-3">Erro ao carregar campos dos relatórios</div>';
    }
}

// Buscar campos reais executando o relatório
async function getFieldsFromReport(reportId) {
    try {
        const formData = new FormData();
        formData.append('report_id', reportId);
        formData.append('query_mode', 'custom_sql');
        
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dynamic-report', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            // Pegar nomes das colunas do primeiro registro
            const fields = Object.keys(result.data[0]);
            console.log(`✅ Campos do relatório ${reportId}:`, fields);
            return fields;
        } else {
            console.warn(`⚠️ Nenhum dado retornado do relatório ${reportId}`);
            // Fallback: tentar extrair do SQL
            const report = selectedReports.find(r => r.id == reportId);
            return extractFieldsFromSQL(report?.sql || '');
        }
    } catch (error) {
        console.error(`❌ Erro ao buscar campos do relatório ${reportId}:`, error);
        return [];
    }
}

// Extrair campos do SELECT da query SQL
function extractFieldsFromSQL(sql) {
    if (!sql) return [];
    
    const fields = [];
    
    // Regex para capturar campos do SELECT (simplificado)
    // Captura: campo AS "alias" ou campo AS alias ou apenas campo
    const regex = /(?:SELECT|,)\s+(?:[\w."]+\s+AS\s+["']?(\w+)["']?|(\w+)\s*(?:,|FROM))/gi;
    let match;
    
    while ((match = regex.exec(sql)) !== null) {
        const fieldName = match[1] || match[2];
        if (fieldName && fieldName !== 'SELECT' && fieldName !== 'FROM') {
            fields.push({ name: fieldName, type: 'field' });
        }
    }
    
    // Remover duplicados
    const unique = [...new Set(fields.map(f => f.name))];
    return unique.map(name => ({ name, type: 'field' }));
}

// Drag and Drop
function initDragAndDrop() {
    const fieldItems = document.querySelectorAll('.field-item');
    
    console.log('🎯 Inicializando drag-and-drop para', fieldItems.length, 'campos');
    
    fieldItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            const data = {
                field: this.dataset.field,
                report: this.dataset.report
            };
            e.dataTransfer.setData('text/plain', JSON.stringify(data));
            e.dataTransfer.effectAllowed = 'copy';
            this.style.opacity = '0.5';
            console.log('🎯 Drag iniciado:', data);
        });
        
        item.addEventListener('dragend', function() {
            this.style.opacity = '1';
        });
        
        // Duplo clique para adicionar rapidamente
        item.addEventListener('dblclick', function() {
            const field = this.dataset.field;
            console.log('👆 Duplo clique no campo:', field);
            if (confirm(`Adicionar "${field}" como KPI?`)) {
                addKpiFromField(field);
            }
        });
    });
    
    // Configurar containers de drop
    setupDropZones();
}

function setupDropZones() {
    const containers = [
        { id: 'kpisContainer', type: 'kpi' },
        { id: 'filtersContainer', type: 'filter' },
        { id: 'chartsContainer', type: 'chart' }
    ];
    
    containers.forEach(({ id, type }) => {
        const container = document.getElementById(id);
        if (!container) {
            console.error('❌ Container não encontrado:', id);
            return;
        }
        
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            this.style.backgroundColor = '#d1e7ff';
            this.style.borderColor = '#0d6efd';
        });
        
        container.addEventListener('dragleave', function(e) {
            this.style.backgroundColor = '#f8f9fa';
            this.style.borderColor = '#dee2e6';
        });
        
        container.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f8f9fa';
            this.style.borderColor = '#dee2e6';
            
            try {
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                console.log('📦 Drop no container:', id, 'Campo:', data.field);
                
                handleFieldDrop(data.field, type);
            } catch (error) {
                console.error('❌ Erro ao processar drop:', error);
            }
        });
        
        console.log('✅ Drop zone configurada:', id);
    });
}

function handleFieldDrop(field, type) {
    console.log('🎯 handleFieldDrop - Campo:', field, 'Tipo:', type);
    
    if (type === 'kpi') {
        addKpiFromField(field);
    } else if (type === 'filter') {
        addFilterFromField(field);
    } else if (type === 'chart') {
        addChartFromField(field);
    }
}

function addFilterFromField(field) {
    const label = prompt('Rótulo do filtro:', field);
    if (!label) return;
    
    const variable = prompt('Variável na query (ex: {VENDEDOR_FILTER}):', `{${field.toUpperCase()}_FILTER}`);
    if (!variable) return;
    
    const type = prompt('Tipo (text, number, year, month):', 'text');
    
    filters.push({ field, label, variable, type });
    updateFiltersDisplay();
}

function addChartFromField(field) {
    const title = prompt('Título do gráfico:', `Gráfico de ${field}`);
    if (!title) return;
    
    const groupBy = prompt('Agrupar por campo (ex: Mes, nomeVendedor):', 'Mes');
    if (!groupBy) return;
    
    const aggregation = prompt('Agregação (sum, avg, count):', 'sum');
    const type = prompt('Tipo de gráfico (bar, line, pie):', 'bar');
    
    charts.push({ title, group_by: groupBy, value_field: field, aggregation, type });
    updateChartsDisplay();
}

function addKpiFromField(field) {
    const aggregation = prompt('Agregação (sum, avg, count, count_distinct, min, max):', 'sum');
    if (!aggregation) return;
    
    const format = prompt('Formato (number, currency, percent):', 'number');
    const icon = prompt('Ícone FontAwesome (ex: fa-dollar-sign):', 'fa-chart-line');
    const color = prompt('Cor (primary, success, danger, warning, info):', 'primary');
    
    kpis.push({ 
        field, 
        label: field, 
        aggregation, 
        format, 
        icon, 
        color 
    });
    updateKpisDisplay();
}

function updateKpisDisplay() {
    const container = document.getElementById('kpisContainer');
    container.innerHTML = '';
    
    kpis.forEach((kpi, index) => {
        const div = document.createElement('div');
        div.className = 'alert alert-' + kpi.color + ' d-flex justify-content-between align-items-center mb-2';
        div.innerHTML = `
            <div>
                <i class="fas ${kpi.icon}"></i> 
                <strong>${kpi.label}</strong>: ${kpi.aggregation}(${kpi.field}) - Formato: ${kpi.format}
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeKpi(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('kpisConfig').value = JSON.stringify(kpis);
}

function removeKpi(index) {
    kpis.splice(index, 1);
    updateKpisDisplay();
}

// Adicionar Filtro
function addFilter() {
    const field = prompt('Campo para filtrar (ex: T7."GroupName", T2."SlpName"):');
    if (!field) return;
    
    const label = prompt('Rótulo do filtro (ex: Vendedor, Grupo):');
    if (!label) return;
    
    const variable = prompt('Variável na query (ex: {VENDEDOR_FILTER}, {GRUPO_FILTER}):');
    if (!variable) return;
    
    const type = prompt('Tipo (text, number, year, month):', 'text');
    
    filters.push({ field, label, variable, type });
    updateFiltersDisplay();
}

function updateFiltersDisplay() {
    const container = document.getElementById('filtersContainer');
    container.innerHTML = '';
    
    filters.forEach((filter, index) => {
        const div = document.createElement('div');
        div.className = 'alert alert-info d-flex justify-content-between align-items-center mb-2';
        div.innerHTML = `
            <div>
                <i class="fas fa-filter"></i> 
                <strong>${filter.label}</strong>: ${filter.field} → ${filter.variable} (${filter.type})
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeFilter(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('filtersConfig').value = JSON.stringify(filters);
}

function removeFilter(index) {
    filters.splice(index, 1);
    updateFiltersDisplay();
}

// Adicionar Gráfico
function addChart() {
    const title = prompt('Título do gráfico (ex: Vendas por Mês):');
    if (!title) return;
    
    const groupBy = prompt('Agrupar por campo (ex: Mes, nomeVendedor):');
    if (!groupBy) return;
    
    const valueField = prompt('Campo de valor (ex: TotalLinha):');
    if (!valueField) return;
    
    const aggregation = prompt('Agregação (sum, avg, count):', 'sum');
    const type = prompt('Tipo de gráfico (bar, line, pie):', 'bar');
    
    charts.push({ title, group_by: groupBy, value_field: valueField, aggregation, type });
    updateChartsDisplay();
}

function updateChartsDisplay() {
    const container = document.getElementById('chartsContainer');
    container.innerHTML = '';
    
    charts.forEach((chart, index) => {
        const div = document.createElement('div');
        div.className = 'alert alert-warning d-flex justify-content-between align-items-center mb-2';
        div.innerHTML = `
            <div>
                <i class="fas fa-chart-${chart.type === 'pie' ? 'pie' : 'bar'}"></i> 
                <strong>${chart.title}</strong>: ${chart.aggregation}(${chart.value_field}) por ${chart.group_by}
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeChart(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('chartsConfig').value = JSON.stringify(charts);
}

function removeChart(index) {
    charts.splice(index, 1);
    updateChartsDisplay();
}
</script>

<style>
.report-card {
    transition: all 0.2s;
    cursor: pointer;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

.report-checkbox:checked + label {
    color: #0d6efd;
    font-weight: bold;
}

.field-item {
    transition: all 0.2s;
}

.field-item:hover {
    background-color: #e7f3ff !important;
    transform: translateX(5px);
}

.field-item:active {
    cursor: grabbing !important;
}

/* Padronizar tamanho de todos os containers */
.config-container,
#kpisContainer,
#filtersContainer,
#chartsContainer {
    min-height: 150px !important;
    max-height: 400px !important;
    overflow-y: auto !important;
    transition: background-color 0.3s, border-color 0.3s;
}

#kpisContainer:empty::after,
#filtersContainer:empty::after,
#chartsContainer:empty::after {
    content: 'Arraste campos aqui ou clique em "Adicionar"';
    display: block;
    text-align: center;
    color: #6c757d;
    padding: 40px 20px;
    font-style: italic;
}

.accordion-button {
    font-size: 0.9rem;
    padding: 0.75rem;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f3ff;
    color: #0d6efd;
}
</style>

