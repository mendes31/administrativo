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
                        
                        <button type="button" class="btn btn-outline-purple btn-sm" data-bs-toggle="modal" data-bs-target="#measureModal" onclick="openMeasureModal()" style="border-color: #6f42c1; color: #6f42c1;">
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
                        
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#kpiModal" onclick="openKpiModal()">
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
                
                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal" onclick="openFilterModal()">
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
                        
                        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#chartModal" onclick="openChartModal()">
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

<!-- MODAIS GRÁFICOS -->

<!-- Modal de Medida -->
<div class="modal fade" id="measureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-calculator"></i> <span id="measureModalTitle">Adicionar Medida Calculada</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="measureEditIndex" value="-1">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Nome da Medida *</label>
                    <input type="text" id="measureName" class="form-control" placeholder="Ex: Ticket Médio, Taxa de Devolução">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Fórmula *</label>
                    <textarea id="measureFormula" class="form-control font-monospace" rows="4" 
                              placeholder="Ex: [Total c/ Desc] / COUNT([NumDoc])

Funções disponíveis:
• SUM([campo]) - Soma
• AVG([campo]) - Média  
• COUNT([campo]) - Contagem
• MIN([campo]) - Mínimo
• MAX([campo]) - Máximo"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Formato de Exibição *</label>
                        <select id="measureFormat" class="form-select">
                            <option value="number">Número (1.234)</option>
                            <option value="currency">Moeda (R$ 1.234,56)</option>
                            <option value="percent">Percentual (12,34%)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="saveMeasure()">
                    <i class="fas fa-check"></i> Salvar Medida
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de KPI -->
<div class="modal fade" id="kpiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-tachometer-alt"></i> <span id="kpiModalTitle">Adicionar KPI</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="kpiEditIndex" value="-1">
                <input type="hidden" id="kpiPreSelectedField" value="">
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-light border">
                            <strong>Configurar KPI:</strong>
                            <br><small class="text-muted">Selecione uma medida calculada ou campo de relatório.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">1️⃣ Tipo de Fonte *</label>
                        <select id="kpiSourceType" class="form-select" onchange="loadKpiFieldsFromSource()">
                            <option value="">-- Escolha --</option>
                            <option value="measure">📐 Medida Calculada</option>
                            <option value="report">📊 Campo de Relatório</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6" id="kpiSourceContainer" style="display: none;">
                        <label class="form-label fw-bold">2️⃣ Selecionar Relatório *</label>
                        <select id="kpiSource" class="form-select" onchange="loadKpiFields()">
                            <option value="">-- Escolha --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6" id="kpiFieldContainer" style="display: none;">
                        <label class="form-label fw-bold">3️⃣ Selecionar Campo *</label>
                        <select id="kpiField" class="form-select">
                            <option value="">-- Escolha o campo --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rótulo (Título) *</label>
                        <input type="text" id="kpiLabel" class="form-control" placeholder="Ex: Faturamento Total">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Agregação</label>
                        <select id="kpiAggregation" class="form-select">
                            <option value="">(Nenhuma - usar medida calculada)</option>
                            <option value="sum">Soma (SUM)</option>
                            <option value="avg">Média (AVG)</option>
                            <option value="count">Contagem (COUNT)</option>
                            <option value="count_distinct">Contagem Única (COUNT DISTINCT)</option>
                            <option value="min">Mínimo (MIN)</option>
                            <option value="max">Máximo (MAX)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Formato</label>
                        <select id="kpiFormat" class="form-select">
                            <option value="number">Número</option>
                            <option value="currency">Moeda (R$)</option>
                            <option value="percent">Percentual (%)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ícone (FontAwesome)</label>
                        <select id="kpiIcon" class="form-select">
                            <option value="fa-dollar-sign">💰 Dinheiro</option>
                            <option value="fa-chart-line">📈 Crescimento</option>
                            <option value="fa-chart-bar">📊 Gráfico</option>
                            <option value="fa-receipt">🧾 Recibo</option>
                            <option value="fa-boxes">📦 Caixas</option>
                            <option value="fa-percentage">% Percentual</option>
                            <option value="fa-coins">🪙 Moedas</option>
                            <option value="fa-file-invoice">📄 Fatura</option>
                            <option value="fa-users">👥 Usuários</option>
                            <option value="fa-shopping-cart">🛒 Carrinho</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cor</label>
                        <select id="kpiColor" class="form-select">
                            <option value="success" style="background-color: #d4edda;">🟢 Verde (Positivo)</option>
                            <option value="primary" style="background-color: #cfe2ff;">🔵 Azul (Informação)</option>
                            <option value="warning" style="background-color: #fff3cd;">🟡 Amarelo (Alerta)</option>
                            <option value="danger" style="background-color: #f8d7da;">🔴 Vermelho (Negativo)</option>
                            <option value="info" style="background-color: #d1ecf1;">💙 Azul Claro</option>
                            <option value="secondary" style="background-color: #e2e3e5;">⚫ Cinza</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="saveKpi()">
                    <i class="fas fa-check"></i> Salvar KPI
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Filtro -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-filter"></i> <span id="filterModalTitle">Adicionar Filtro</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="filterEditIndex" value="-1">
                <input type="hidden" id="filterPreSelectedField" value="">
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-light border">
                            <strong>Selecione o Campo para Filtrar:</strong>
                            <br><small class="text-muted">Escolha o relatório e depois o campo específico.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">1️⃣ Selecionar Relatório *</label>
                        <select id="filterReportSource" class="form-select" onchange="loadFilterFields()">
                            <option value="">-- Escolha o relatório --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6" id="filterFieldContainer" style="display: none;">
                        <label class="form-label fw-bold">2️⃣ Selecionar Campo *</label>
                        <select id="filterField" class="form-select">
                            <option value="">-- Escolha o campo --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rótulo (Título) *</label>
                        <input type="text" id="filterLabel" class="form-control" placeholder="Ex: Vendedor, Período, Categoria">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Filtro *</label>
                        <select id="filterType" class="form-select">
                            <option value="text">📝 Texto (dropdown ou input)</option>
                            <option value="number">🔢 Número</option>
                            <option value="date">📅 Data</option>
                            <option value="year">📆 Ano</option>
                            <option value="month">📅 Mês</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Valor Padrão</label>
                        <input type="text" id="filterDefaultValue" class="form-control" placeholder="Ex: 2025, João Silva">
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="filterRequired" class="form-check-input">
                            <label class="form-check-label" for="filterRequired">
                                <i class="fas fa-exclamation-circle"></i> Campo obrigatório
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Relatório de Filtro (Opcional)</label>
                        <select id="filterReportId" class="form-select">
                            <option value="">Usar query do dashboard</option>
                            <option value="15">[FILTRO] Vendedores</option>
                            <option value="16">[FILTRO] Grupos de Parceiros</option>
                            <option value="18">[FILTRO] Itens</option>
                            <option value="19">[FILTRO] Parceiros</option>
                        </select>
                        <small class="text-muted">Para listar TODOS os valores (sem limite)</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" onclick="saveFilter()">
                    <i class="fas fa-check"></i> Salvar Filtro
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Gráfico -->
<div class="modal fade" id="chartModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-chart-bar"></i> <span id="chartModalTitle">Adicionar Gráfico</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="chartEditKey" value="">
                <input type="hidden" id="chartPreSelectedGroupBy" value="">
                <input type="hidden" id="chartPreSelectedValue" value="">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Gráfico *</label>
                        <select id="chartType" class="form-select">
                            <option value="bar">📊 Barras</option>
                            <option value="line">📈 Linhas</option>
                            <option value="pie">🥧 Pizza</option>
                            <option value="doughnut">🍩 Rosca</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Título do Gráfico *</label>
                        <input type="text" id="chartTitle" class="form-control" placeholder="Ex: Faturamento por Vendedor">
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <strong>Eixo X (Categorias):</strong>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">1️⃣ Relatório para Agrupar *</label>
                        <select id="chartGroupByReport" class="form-select" onchange="loadChartGroupByFields()">
                            <option value="">-- Escolha o relatório --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6" id="chartGroupByContainer" style="display: none;">
                        <label class="form-label fw-bold">2️⃣ Campo de Agrupamento (Eixo X) *</label>
                        <select id="chartGroupBy" class="form-select">
                            <option value="">-- Escolha o campo --</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <strong>Eixo Y (Valores):</strong>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">1️⃣ Relatório para Valores *</label>
                        <select id="chartValueReport" class="form-select" onchange="loadChartValueFields()">
                            <option value="">-- Escolha o relatório --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6" id="chartValueContainer" style="display: none;">
                        <label class="form-label fw-bold">2️⃣ Campo de Valor (Eixo Y) *</label>
                        <select id="chartValueField" class="form-select">
                            <option value="">-- Escolha o campo --</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Agregação *</label>
                        <select id="chartAggregation" class="form-select">
                            <option value="sum">Soma (SUM)</option>
                            <option value="avg">Média (AVG)</option>
                            <option value="count">Contagem (COUNT)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cor (Opcional)</label>
                        <input type="color" id="chartColor" class="form-control form-control-color" value="#4CAF50">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="saveChart()">
                    <i class="fas fa-check"></i> Salvar Gráfico
                </button>
            </div>
        </div>
    </div>
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
    
    // Os botões agora usam modais (data-bs-toggle e onclick definidos no HTML)
    // Apenas garantir que as funções de modal estejam disponíveis
    
    console.log('✅ Eventos da etapa 2 prontos (usando modais)!');
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

// ========== FUNÇÕES DOS MODAIS GRÁFICOS ==========

// Funções de Modal de Medida
function openMeasureModal(preFilledName = '', preFilledFormula = '') {
    document.getElementById('measureModalTitle').textContent = 'Adicionar Medida Calculada';
    document.getElementById('measureName').value = preFilledName;
    document.getElementById('measureFormula').value = preFilledFormula;
    document.getElementById('measureFormat').value = 'number';
    document.getElementById('measureEditIndex').value = '-1';
    
    new bootstrap.Modal(document.getElementById('measureModal')).show();
}

function saveMeasure() {
    const name = document.getElementById('measureName').value.trim();
    const formula = document.getElementById('measureFormula').value.trim();
    const format = document.getElementById('measureFormat').value;
    
    if (!name || !formula) {
        alert('❌ Nome e fórmula são obrigatórios!');
        return;
    }
    
    if (!validateFormula(formula)) {
        alert('❌ Fórmula inválida! Use apenas funções permitidas e operadores matemáticos.');
        return;
    }
    
    measures.push({ name, formula, format });
    updateMeasuresDisplay();
    updateFieldsPanel();
    
    bootstrap.Modal.getInstance(document.getElementById('measureModal')).hide();
    console.log('✅ Medida criada:', name);
}

// Funções de Modal de KPI
function openKpiModal(preSelectedField = '') {
    document.getElementById('kpiModalTitle').textContent = 'Adicionar KPI';
    document.getElementById('kpiSourceType').value = '';
    document.getElementById('kpiSource').value = '';
    document.getElementById('kpiField').value = '';
    document.getElementById('kpiLabel').value = '';
    document.getElementById('kpiAggregation').value = 'sum';
    document.getElementById('kpiFormat').value = 'currency';
    document.getElementById('kpiIcon').value = 'fa-dollar-sign';
    document.getElementById('kpiColor').value = 'success';
    document.getElementById('kpiEditIndex').value = '-1';
    document.getElementById('kpiPreSelectedField').value = preSelectedField;
    
    // Esconder containers
    document.getElementById('kpiSourceContainer').style.display = 'none';
    document.getElementById('kpiFieldContainer').style.display = 'none';
    
    // Popular dropdowns
    populateReportDropdowns();
    
    // Se houver campo pré-selecionado, configurar
    if (preSelectedField) {
        document.getElementById('kpiSourceType').value = 'report';
        loadKpiFieldsFromSource();
        if (selectedReports.length > 0) {
            document.getElementById('kpiSource').value = selectedReports[0].id;
            loadKpiFields();
            setTimeout(() => {
                document.getElementById('kpiField').value = preSelectedField;
                document.getElementById('kpiLabel').value = preSelectedField;
            }, 300);
        }
    }
    
    new bootstrap.Modal(document.getElementById('kpiModal')).show();
}

function saveKpi() {
    const field = document.getElementById('kpiField').value;
    const label = document.getElementById('kpiLabel').value.trim();
    const aggregation = document.getElementById('kpiAggregation').value;
    const format = document.getElementById('kpiFormat').value;
    const icon = document.getElementById('kpiIcon').value;
    const color = document.getElementById('kpiColor').value;
    const sourceType = document.getElementById('kpiSourceType').value;
    const source = document.getElementById('kpiSource').value;
    
    if (!field) {
        alert('❌ Selecione um campo ou medida!');
        return;
    }
    
    if (!label) {
        alert('❌ Rótulo é obrigatório!');
        return;
    }
    
    const kpiData = { 
        field, 
        label, 
        aggregation, 
        format, 
        icon, 
        color,
        source_type: sourceType,
        source_report_id: sourceType === 'report' ? source : null,
        source_measure: sourceType === 'measure' ? field : null
    };
    
    kpis.push(kpiData);
    updateKpisDisplay();
    
    bootstrap.Modal.getInstance(document.getElementById('kpiModal')).hide();
    console.log('✅ KPI adicionado:', label);
}

// Funções de Modal de Filtro
function openFilterModal(preSelectedField = '') {
    document.getElementById('filterModalTitle').textContent = 'Adicionar Filtro';
    document.getElementById('filterReportSource').value = '';
    document.getElementById('filterField').value = '';
    document.getElementById('filterLabel').value = '';
    document.getElementById('filterType').value = 'text';
    document.getElementById('filterDefaultValue').value = '';
    document.getElementById('filterRequired').checked = false;
    document.getElementById('filterReportId').value = '';
    document.getElementById('filterEditIndex').value = '-1';
    document.getElementById('filterPreSelectedField').value = preSelectedField;
    
    // Esconder container de campo
    document.getElementById('filterFieldContainer').style.display = 'none';
    
    // Popular dropdown
    populateFilterReportDropdowns();
    
    // Se houver campo pré-selecionado
    if (preSelectedField && selectedReports.length > 0) {
        document.getElementById('filterReportSource').value = selectedReports[0].id;
        loadFilterFields();
        setTimeout(() => {
            document.getElementById('filterField').value = preSelectedField;
            document.getElementById('filterLabel').value = preSelectedField;
        }, 300);
    }
    
    new bootstrap.Modal(document.getElementById('filterModal')).show();
}

function saveFilter() {
    const field = document.getElementById('filterField').value;
    const label = document.getElementById('filterLabel').value.trim();
    const type = document.getElementById('filterType').value;
    const defaultValue = document.getElementById('filterDefaultValue').value;
    const required = document.getElementById('filterRequired').checked;
    const filterReportId = document.getElementById('filterReportId').value;
    const sourceReportId = document.getElementById('filterReportSource').value;
    
    if (!field) {
        alert('❌ Selecione um campo!');
        return;
    }
    
    if (!label) {
        alert('❌ Rótulo é obrigatório!');
        return;
    }
    
    const filterData = { 
        field, 
        label, 
        type, 
        default_value: defaultValue,
        required: required,
        filter_report_id: filterReportId ? parseInt(filterReportId) : null,
        source_report_id: sourceReportId ? parseInt(sourceReportId) : null
    };
    
    filters.push(filterData);
    updateFiltersDisplay();
    
    bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
    console.log('✅ Filtro adicionado:', label);
}

// Funções de Modal de Gráfico
function openChartModal(preSelectedGroupBy = '', preSelectedValue = '') {
    document.getElementById('chartModalTitle').textContent = 'Adicionar Gráfico';
    document.getElementById('chartType').value = 'bar';
    document.getElementById('chartTitle').value = '';
    document.getElementById('chartGroupByReport').value = '';
    document.getElementById('chartGroupBy').value = '';
    document.getElementById('chartValueReport').value = '';
    document.getElementById('chartValueField').value = '';
    document.getElementById('chartAggregation').value = 'sum';
    document.getElementById('chartColor').value = '#4CAF50';
    document.getElementById('chartEditKey').value = '';
    document.getElementById('chartPreSelectedGroupBy').value = preSelectedGroupBy;
    document.getElementById('chartPreSelectedValue').value = preSelectedValue;
    
    // Esconder containers
    document.getElementById('chartGroupByContainer').style.display = 'none';
    document.getElementById('chartValueContainer').style.display = 'none';
    
    // Popular dropdowns
    populateChartReportDropdowns();
    
    // Se houver campos pré-selecionados
    if (selectedReports.length > 0) {
        if (preSelectedGroupBy) {
            document.getElementById('chartGroupByReport').value = selectedReports[0].id;
            loadChartGroupByFields();
            setTimeout(() => {
                document.getElementById('chartGroupBy').value = preSelectedGroupBy;
            }, 300);
        }
        if (preSelectedValue) {
            document.getElementById('chartValueReport').value = selectedReports[0].id;
            loadChartValueFields();
            setTimeout(() => {
                document.getElementById('chartValueField').value = preSelectedValue;
            }, 300);
        }
    }
    
    new bootstrap.Modal(document.getElementById('chartModal')).show();
}

function saveChart() {
    const type = document.getElementById('chartType').value;
    const title = document.getElementById('chartTitle').value.trim();
    const groupBy = document.getElementById('chartGroupBy').value;
    const valueField = document.getElementById('chartValueField').value;
    const aggregation = document.getElementById('chartAggregation').value;
    const color = document.getElementById('chartColor').value;
    const groupByReport = document.getElementById('chartGroupByReport').value;
    const valueReport = document.getElementById('chartValueReport').value;
    
    if (!title) {
        alert('❌ Título é obrigatório!');
        return;
    }
    
    if (!groupBy) {
        alert('❌ Selecione o campo de agrupamento (Eixo X)!');
        return;
    }
    
    if (!valueField) {
        alert('❌ Selecione o campo de valor (Eixo Y)!');
        return;
    }
    
    const chartData = { 
        type, 
        title, 
        group_by: groupBy, 
        value_field: valueField, 
        aggregation, 
        color,
        group_by_report_id: groupByReport ? parseInt(groupByReport) : null,
        value_report_id: valueReport ? parseInt(valueReport) : null
    };
    
    const newKey = 'chart' + (Object.keys(charts).length + 1);
    charts[newKey] = chartData;
    updateChartsDisplay();
    
    bootstrap.Modal.getInstance(document.getElementById('chartModal')).hide();
    console.log('✅ Gráfico adicionado:', title);
}

// Funções de suporte para popula dropdowns
function populateReportDropdowns() {
    const kpiSource = document.getElementById('kpiSource');
    if (kpiSource) {
        kpiSource.innerHTML = '<option value="">-- Escolha --</option>';
        selectedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.id;
            opt.textContent = report.name;
            kpiSource.appendChild(opt);
        });
    }
}

function populateFilterReportDropdowns() {
    const filterReportSource = document.getElementById('filterReportSource');
    if (!filterReportSource) return;
    
    filterReportSource.innerHTML = '<option value="">-- Escolha o relatório --</option>';
    selectedReports.forEach(report => {
        const opt = document.createElement('option');
        opt.value = report.id;
        opt.textContent = report.name;
        filterReportSource.appendChild(opt);
    });
}

function populateChartReportDropdowns() {
    const groupByReport = document.getElementById('chartGroupByReport');
    const valueReport = document.getElementById('chartValueReport');
    
    if (groupByReport) {
        groupByReport.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        selectedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.id;
            opt.textContent = report.name;
            groupByReport.appendChild(opt);
        });
    }
    
    if (valueReport) {
        valueReport.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        selectedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.id;
            opt.textContent = report.name;
            valueReport.appendChild(opt);
        });
    }
}

// Funções de cascata para carregar campos
function loadKpiFieldsFromSource() {
    const sourceType = document.getElementById('kpiSourceType').value;
    const sourceContainer = document.getElementById('kpiSourceContainer');
    const fieldContainer = document.getElementById('kpiFieldContainer');
    
    if (!sourceType) {
        sourceContainer.style.display = 'none';
        fieldContainer.style.display = 'none';
        return;
    }
    
    if (sourceType === 'measure') {
        // Usar medidas calculadas - pular etapa 2, mostrar campos diretamente
        sourceContainer.style.display = 'none';
        fieldContainer.style.display = 'block';
        
        const fieldSelect = document.getElementById('kpiField');
        fieldSelect.innerHTML = '<option value="">-- Escolha a medida --</option>';
        
        measures.forEach(measure => {
            const opt = document.createElement('option');
            opt.value = measure.name;
            opt.textContent = measure.name;
            fieldSelect.appendChild(opt);
        });
    } else {
        // Usar campos de relatório
        sourceContainer.style.display = 'block';
        fieldContainer.style.display = 'none';
        
        const sourceSelect = document.getElementById('kpiSource');
        sourceSelect.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        selectedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.id;
            opt.textContent = report.name;
            sourceSelect.appendChild(opt);
        });
    }
}

function loadKpiFields() {
    const reportId = document.getElementById('kpiSource').value;
    const fieldContainer = document.getElementById('kpiFieldContainer');
    const fieldSelect = document.getElementById('kpiField');
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    const reportData = availableFields[reportId];
    if (reportData && reportData.fields) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    }
}

function loadFilterFields() {
    const reportId = document.getElementById('filterReportSource').value;
    const fieldContainer = document.getElementById('filterFieldContainer');
    const fieldSelect = document.getElementById('filterField');
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    const reportData = availableFields[reportId];
    if (reportData && reportData.fields) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    }
}

function loadChartGroupByFields() {
    const reportId = document.getElementById('chartGroupByReport').value;
    const fieldContainer = document.getElementById('chartGroupByContainer');
    const fieldSelect = document.getElementById('chartGroupBy');
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    const reportData = availableFields[reportId];
    if (reportData && reportData.fields) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    }
}

function loadChartValueFields() {
    const reportId = document.getElementById('chartValueReport').value;
    const fieldContainer = document.getElementById('chartValueContainer');
    const fieldSelect = document.getElementById('chartValueField');
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    const reportData = availableFields[reportId];
    if (reportData && reportData.fields) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    }
}

// ========== FIM DAS FUNÇÕES DOS MODAIS ==========

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

