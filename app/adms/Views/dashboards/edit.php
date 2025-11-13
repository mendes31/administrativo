<?php
$dashboard = $this->data['dashboard'] ?? [];
$reports = $this->data['reports'] ?? [];
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
        <i class="fas fa-magic"></i>
        <strong>Edição Estilo Power BI:</strong> Use as abas abaixo para gerenciar todos os aspectos do dashboard.
        <br><small>✨ Adicione múltiplos relatórios, combine dados, crie medidas calculadas e insights poderosos!</small>
    </div>

    <form method="POST" action="" id="dashboardForm">
        <input type="hidden" name="dashboard_id" id="dashboardIdInput" value="<?= $dashboard['id'] ?>">
        <script>
            // Log para verificar se o campo foi renderizado corretamente
            console.log('📝 Campo dashboard_id renderizado com valor:', <?= $dashboard['id'] ?? 'INDEFINIDO' ?>);
            console.log('📝 Elemento dashboard_id:', document.getElementById('dashboardIdInput'));
        </script>
        
        <!-- Abas Estilo Power BI -->
        <ul class="nav nav-tabs mb-3" id="editTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basicInfo" type="button">
                    <i class="fas fa-info-circle"></i> Informações
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reportsConfig" type="button">
                    <i class="fas fa-database"></i> Fontes de Dados <span class="badge bg-primary" id="reportsCount">0</span>
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
            <li class="nav-item">
                <button class="nav-link" id="relationships-tab" data-bs-toggle="tab" data-bs-target="#relationshipsConfig" type="button">
                    <i class="fas fa-project-diagram"></i> Relacionamentos <span class="badge bg-secondary" id="relationshipsCount">0</span>
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
                                       placeholder="Ex: Vendas, Financeiro, Estoque"
                                       value="<?= htmlspecialchars($dashboard['category'] ?? '') ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold">Descrição</label>
                                <textarea name="description" class="form-control" rows="3" 
                                          placeholder="Descreva o objetivo deste dashboard..."><?= htmlspecialchars($dashboard['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" class="form-check-input" id="is_public" 
                                           value="1" <?= $dashboard['is_public'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_public">
                                        <i class="fas fa-globe"></i> Dashboard Público (visível para todos os usuários)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ABA 2: Fontes de Dados (Relatórios) -->
            <div class="tab-pane fade" id="reportsConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-database"></i> Fontes de Dados (Relatórios)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Estilo Power BI:</strong> Adicione múltiplos relatórios para combinar dados de diferentes fontes.
                            <br>Os campos de TODOS os relatórios estarão disponíveis para criar medidas, KPIs e gráficos.
                        </div>
                        
                        <h6 class="fw-bold mb-3">📊 Relatórios Vinculados:</h6>
                        <div id="linkedReportsContainer" class="mb-3">
                            <!-- Relatórios renderizados via JavaScript -->
                        </div>
                        
                        <hr>
                        
                        <h6 class="fw-bold mb-3">➕ Adicionar Novo Relatório:</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label class="form-label">Selecione o Relatório:</label>
                                <select id="newReportSelect" class="form-select">
                                    <option value="">-- Escolha um relatório --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success w-100" id="addReportBtn">
                                    <i class="fas fa-plus"></i> Adicionar
                                </button>
                            </div>
                        </div>
                        
                        <input type="hidden" name="report_ids" id="reportIdsInput">
                    </div>
                </div>
            </div>
            
            <!-- ABA 3: Medidas Calculadas -->
            <div class="tab-pane fade" id="measuresConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-calculator"></i> Medidas Calculadas</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-magic"></i> 
                            Crie fórmulas personalizadas combinando campos de múltiplos relatórios.
                        </p>
                        
                        <div id="measuresContainer" class="mb-3">
                            <!-- Medidas renderizadas aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#measureModal" onclick="openMeasureModal()">
                            <i class="fas fa-plus"></i> Adicionar Nova Medida
                        </button>
                        
                        <input type="hidden" name="measures_config" id="measuresConfigInput">
                    </div>
                </div>
            </div>
            
            <!-- ABA 4: KPIs -->
            <div class="tab-pane fade" id="kpisConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> KPIs (Indicadores)</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-chart-line"></i> 
                            Configure indicadores principais que aparecerão em destaque no dashboard.
                        </p>
                        
                        <div id="kpisContainer" class="mb-3">
                            <!-- KPIs renderizados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#kpiModal" onclick="openKpiModal()">
                            <i class="fas fa-plus"></i> Adicionar Novo KPI
                        </button>
                        
                        <input type="hidden" name="kpis_config" id="kpisConfigInput">
                    </div>
                </div>
            </div>
            
            <!-- ABA 5: Filtros -->
            <div class="tab-pane fade" id="filtersConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros Dinâmicos</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-sliders-h"></i> 
                            Configure filtros para permitir análise interativa dos dados.
                        </p>
                        
                        <div id="filtersContainer" class="mb-3">
                            <!-- Filtros renderizados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-info btn-lg" data-bs-toggle="modal" data-bs-target="#filterModal" onclick="openFilterModal()">
                            <i class="fas fa-plus"></i> Adicionar Novo Filtro
                        </button>
                        
                        <input type="hidden" name="filters_config" id="filtersConfigInput">
                    </div>
                </div>
            </div>
            
            <!-- ABA 6: Gráficos -->
            <div class="tab-pane fade" id="chartsConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Gráficos e Visualizações</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-chart-pie"></i> 
                            Crie visualizações para apresentar os dados de forma gráfica.
                        </p>
                        
                        <div id="chartsContainer" class="mb-3">
                            <!-- Gráficos renderizados aqui -->
                        </div>
                        
                        <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#chartModal" onclick="openChartModal()">
                            <i class="fas fa-plus"></i> Adicionar Novo Gráfico
                        </button>
                        
                        <input type="hidden" name="charts_config" id="chartsConfigInput">
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="relationshipsConfig" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-project-diagram"></i> Relacionamentos entre Fontes</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-link"></i>
                            Conecte relatórios usando campos-chave para combinar dados e propagar filtros como no Power BI.
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-8">
                        <div class="relationship-canvas border rounded p-3 bg-light position-relative" id="relationshipsCanvas"></div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header bg-light fw-bold">
                                        <i class="fas fa-plus-circle"></i> Nova Conexão
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Relatório Primário</label>
                                            <select class="form-select" id="relationshipPrimaryReport"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Campo Primário</label>
                                            <select class="form-select" id="relationshipPrimaryField"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Relatório Relacionado</label>
                                            <select class="form-select" id="relationshipForeignReport"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Campo Relacionado</label>
                                            <select class="form-select" id="relationshipForeignField"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Relacionamento</label>
                                            <select class="form-select" id="relationshipType">
                                                <option value="one_to_many">1 : N (Um para muitos)</option>
                                                <option value="many_to_one">N : 1 (Muitos para um)</option>
                                                <option value="many_to_many">N : N (Muitos para muitos)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Direção do Filtro</label>
                                            <select class="form-select" id="relationshipFilterDirection">
                                                <option value="bidirectional">Bidirecional</option>
                                                <option value="primary_to_foreign">Primário → Relacionado</option>
                                                <option value="foreign_to_primary">Relacionado → Primário</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Junção</label>
                                            <select class="form-select" id="relationshipJoinType">
                                                <option value="inner">INNER JOIN</option>
                                                <option value="left">LEFT JOIN</option>
                                            </select>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" value="1" id="relationshipActive" checked>
                                            <label class="form-check-label" for="relationshipActive">Relacionamento ativo</label>
                                        </div>
                                        <button type="button" class="btn btn-warning w-100" id="addRelationshipBtn">
                                            <i class="fas fa-link"></i> Criar Relacionamento
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <i class="fas fa-lightbulb"></i> Adicione os relatórios na aba "Fontes de Dados" antes de configurar um relacionamento.
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold">Relacionamentos existentes</h6>
                        <div id="relationshipsList" class="list-group mb-3"></div>

                        <input type="hidden" name="relationships" id="relationshipsConfigInput">
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação Fixos -->
        <div class="card shadow-sm mt-4 sticky-bottom bg-light">
            <div class="card-body">
                <div class="d-flex gap-2 justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-save text-muted"></i>
                        <small class="text-muted">Lembre-se de salvar após fazer alterações</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                        <a href="<?= $_ENV['URL_ADM'] ?>view-dashboard/<?= $dashboard['id'] ?>" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAIS DE EDIÇÃO -->

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
• MAX([campo]) - Máximo
• CALCULATE(expressão;filtro) - Com filtro

Referências:
• [Campo] - Campo simples
• Relatório[Campo] - Com nome do relatório"></textarea>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Use os campos da aba "Campos" para referência
                    </small>
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
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-light border">
                            <strong>Selecione o Campo:</strong>
                            <br><small class="text-muted">Escolha primeiro o relatório, depois o campo específico.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">1️⃣ Selecionar Relatório/Medida *</label>
                        <select id="kpiSourceType" class="form-select" onchange="loadKpiFieldsFromSource()">
                            <option value="">-- Escolha a fonte --</option>
                            <option value="measure">📐 Medida Calculada</option>
                            <option value="report">📊 Campo de Relatório</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6" id="kpiSourceContainer" style="display: none;">
                        <label class="form-label fw-bold">2️⃣ Selecionar Fonte *</label>
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
// Configurações globais
let measures = <?= json_encode($measuresConfig) ?>;
let kpis = <?= json_encode($kpisConfig) ?>;
let filters = <?= json_encode($filtersConfig) ?>;
let charts = <?= json_encode($chartsConfig) ?>;
let relationships = <?= json_encode($dashboard['relationships'] ?? []) ?>;
let linkedReports = <?= json_encode($dashboard['reports'] ?? []) ?>;
let availableReports = <?= json_encode($reports) ?>;
let availableFields = {};
let relationshipEditIndex = -1;
let currentRelationshipSelection = null;

const RELATIONSHIP_NODE_WIDTH = 220;
const RELATIONSHIP_NODE_HEIGHT = 140;
let relationshipPositions = {};
let relationshipLayoutMeta = { width: null, height: null };
const relationshipCanvasState = { canvas: null, svg: null, edgesGroup: null, nodeElements: {}, edges: [], listItems: {} };
let relationshipRenderTimer = null;

if (!Array.isArray(relationships)) {
    relationships = [];
}

console.log('📊 Dashboard carregado:', { measures, kpis, filters, charts, relationships, linkedReports });

function getReportFields(reportId) {
    const numericId = parseInt(reportId);
    const sources = [
        availableFields[numericId],
        availableFields[String(numericId)],
        availableFields[reportId]
    ];

    for (const source of sources) {
        if (!source) {
            continue;
        }

        if (Array.isArray(source)) {
            return source;
        }

        if (source.fields && Array.isArray(source.fields)) {
            return source.fields;
        }
    }

    return [];
}

// ========== INICIALIZAÇÃO DOS CAMPOS HIDDEN ==========
// CRÍTICO: Inicializar campos hidden no carregamento da página
// Se não fizer isso, ao clicar em "Salvar" sem ter editado nada, 
// o servidor receberá valores vazios e apagará todas as configurações!
function initializeHiddenFields() {
    document.getElementById('measuresConfigInput').value = JSON.stringify(measures);
    document.getElementById('kpisConfigInput').value = JSON.stringify(kpis);
    document.getElementById('filtersConfigInput').value = JSON.stringify(filters);
    document.getElementById('chartsConfigInput').value = JSON.stringify(charts);
    const relationshipsInput = document.getElementById('relationshipsConfigInput');
    if (relationshipsInput) {
        relationshipsInput.value = JSON.stringify(relationships);
    }
    document.getElementById('reportIdsInput').value = JSON.stringify(linkedReports.map(r => r.report_id));
    
    console.log('✅ Campos hidden inicializados');
    console.log('   measures:', measures.length);
    console.log('   kpis:', kpis.length);
    console.log('   filters:', filters.length);
    console.log('   charts:', Object.keys(charts).length);
    console.log('   reports:', linkedReports.length);
    console.log('   relationships:', relationships.length);
}

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', function() {
    initializeHiddenFields(); // PRIMEIRO: inicializar campos hidden com dados existentes
    updateLinkedReportsDisplay();
    loadFieldsFromReports();
    updateMeasuresDisplay();
    updateKpisDisplay();
    updateFiltersDisplay();
    updateChartsDisplay();
    updateRelationshipsDisplay();
    refreshRelationshipDropdowns();
    
    // Event delegation para botões de relatórios (evita múltiplos listeners)
    const linkedReportsContainer = document.getElementById('linkedReportsContainer');
    if (linkedReportsContainer) {
        linkedReportsContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-report-btn');
            const primaryBtn = e.target.closest('.set-primary-btn');
            
            if (removeBtn) {
                const index = parseInt(removeBtn.getAttribute('data-index'));
                console.log('🔘 Botão Remover clicado - índice do botão:', index);
                removeLinkedReport(index);
            } else if (primaryBtn) {
                const index = parseInt(primaryBtn.getAttribute('data-index'));
                setPrimaryReport(index);
            }
        });
    }

    // Eventos de relacionamento
    document.getElementById('relationshipPrimaryReport')?.addEventListener('change', () => fillRelationshipFields('primary'));
    document.getElementById('relationshipForeignReport')?.addEventListener('change', () => fillRelationshipFields('foreign'));
    document.getElementById('addRelationshipBtn')?.addEventListener('click', addOrUpdateRelationship);

    const relationshipsList = document.getElementById('relationshipsList');
    if (relationshipsList) {
        relationshipsList.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-relationship-btn');
            const editBtn = e.target.closest('.edit-relationship-btn');
            if (removeBtn) {
                const index = parseInt(removeBtn.getAttribute('data-index'));
                removeRelationship(index);
            } else if (editBtn) {
                const index = parseInt(editBtn.getAttribute('data-index'));
                editRelationship(index);
            }
        });
    }
});

// ========== FONTES DE DADOS (RELATÓRIOS) ==========

function updateLinkedReportsDisplay() {
    const container = document.getElementById('linkedReportsContainer');
    
    if (linkedReports.length === 0) {
        container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nenhum relatório vinculado! Adicione pelo menos 1.</div>';
        document.getElementById('reportsCount').textContent = '0';
        return;
    }
    
    container.innerHTML = '';
    console.log('🔄 updateLinkedReportsDisplay() - Renderizando', linkedReports.length, 'relatórios');
    
    linkedReports.forEach((report, index) => {
        console.log(`   [${index}] Renderizando: ${report.name} (ID: ${report.report_id})`);
        
        const div = document.createElement('div');
        div.className = 'card mb-2';
        div.style.borderLeft = report.is_primary ? '4px solid #0d6efd' : '4px solid #6c757d';
        
        // Usar data-attribute para armazenar o índice real (mais seguro que onclick inline)
        div.setAttribute('data-report-index', index);
        
        div.innerHTML = `
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div style="flex: 1;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-database ${report.is_primary ? 'text-primary' : 'text-secondary'}"></i>
                            <strong>${report.name || 'Sem nome'}</strong>
                            ${report.is_primary ? '<span class="badge bg-primary">Principal</span>' : ''}
                            ${report.category ? '<span class="badge bg-secondary">' + report.category + '</span>' : ''}
                        </div>
                        ${report.description ? '<small class="text-muted d-block mt-1">' + report.description + '</small>' : ''}
                        <small class="text-muted">ID: ${report.report_id} | Fonte: ${report.data_source || 'N/A'}</small>
                    </div>
                    <div class="d-flex gap-1">
                        ${!report.is_primary && linkedReports.length > 1 ? `
                            <button type="button" class="btn btn-sm btn-outline-primary set-primary-btn" data-index="${index}" title="Tornar relatório principal">
                                <i class="fas fa-star"></i> Principal
                            </button>
                        ` : ''}
                        ${linkedReports.length > 1 ? `
                            <button type="button" class="btn btn-sm btn-outline-danger remove-report-btn" data-index="${index}">
                                <i class="fas fa-trash"></i> Remover
                            </button>
                        ` : '<span class="badge bg-secondary">Obrigatório</span>'}
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
    
    const reportIds = linkedReports.map(r => r.report_id);
    document.getElementById('reportIdsInput').value = JSON.stringify(reportIds);
    document.getElementById('reportsCount').textContent = linkedReports.length;
    
    updateReportDropdown();
    loadFieldsFromReports(); // Recarregar campos
    renderRelationshipsCanvas();
}

function updateReportDropdown() {
    const select = document.getElementById('newReportSelect');
    const linkedIds = linkedReports.map(r => r.report_id);
    
    select.innerHTML = '<option value="">-- Escolha um relatório --</option>';
    
    availableReports.forEach(report => {
        if (!linkedIds.includes(report.id)) {
            const option = document.createElement('option');
            option.value = report.id;
            option.textContent = report.name + (report.category ? ' - ' + report.category : '');
            select.appendChild(option);
        }
    });
}

document.getElementById('addReportBtn')?.addEventListener('click', async function() {
    const select = document.getElementById('newReportSelect');
    const reportId = parseInt(select.value);
    
    if (!reportId) {
        alert('❌ Selecione um relatório!');
        return;
    }
    
    const report = availableReports.find(r => r.id === reportId);
    
    if (!report) {
        alert('❌ Relatório não encontrado!');
        return;
    }
    
    linkedReports.push({
        report_id: reportId,
        name: report.name,
        description: report.description,
        category: report.category,
        data_source: report.data_source,
        query_mode: report.query_mode,
        is_primary: linkedReports.length === 0,
        display_order: linkedReports.length + 1
    });

    relationshipPositions = {};
    relationshipLayoutMeta = { width: null, height: null };
    
    updateLinkedReportsDisplay();
    refreshRelationshipDropdowns();
    updateRelationshipsDisplay();
    
    console.log('✅ Relatório adicionado:', report.name);
});

function removeLinkedReport(index) {
    console.log('🗑️ removeLinkedReport() chamado:');
    console.log('   Índice recebido:', index);
    console.log('   linkedReports ANTES:', JSON.parse(JSON.stringify(linkedReports)));
    console.log('   Total de relatórios:', linkedReports.length);
    
    if (linkedReports.length === 1) {
        alert('❌ Não é possível remover o último relatório!\n\nUm dashboard deve ter pelo menos 1 relatório.');
        return;
    }
    
    // Validar índice
    if (index < 0 || index >= linkedReports.length) {
        console.error('❌ Índice inválido!', index, 'de', linkedReports.length);
        alert('❌ Erro: Índice inválido. Recarregue a página e tente novamente.');
        return;
    }
    
    const report = linkedReports[index];
    console.log('   Relatório a remover:', report.name, '(ID:', report.report_id, ')');
    
    if (!confirm(`❌ Remover relatório "${report.name}"?\n\n⚠️ ATENÇÃO: Medidas, KPIs e gráficos que usam campos deste relatório podem parar de funcionar!`)) {
        return;
    }
    
    // Remover apenas o item no índice especificado
    const removed = linkedReports.splice(index, 1);
    console.log('   Item removido:', removed);
    console.log('   linkedReports DEPOIS:', JSON.parse(JSON.stringify(linkedReports)));
    
    if (linkedReports.length > 0) {
        linkedReports[0].is_primary = true;
        console.log('   Novo relatório principal:', linkedReports[0].name);
    }
    
    updateLinkedReportsDisplay();
    console.log('✅ Display atualizado');

    // Remover relacionamentos que dependiam do relatório excluído
    const removedReportId = removed[0]?.report_id;
    if (removedReportId) {
        const originalLength = relationships.length;
        relationships = relationships.filter(rel => rel.primary_report_id !== removedReportId && rel.foreign_report_id !== removedReportId);
        if (relationships.length !== originalLength) {
            console.log('🔄 Relacionamentos atualizados após remoção de relatório.');
        }

        if (relationships.length === 0) {
            currentRelationshipSelection = null;
        } else if (currentRelationshipSelection !== null) {
            if (currentRelationshipSelection >= relationships.length) {
                currentRelationshipSelection = relationships.length - 1;
            }
        }

        updateRelationshipsDisplay();
        refreshRelationshipDropdowns();
    }

    if (removedReportId) {
        delete relationshipPositions[removedReportId];
        relationshipLayoutMeta = { width: null, height: null };
    }
}

function setPrimaryReport(index) {
    linkedReports.forEach(r => r.is_primary = false);
    linkedReports[index].is_primary = true;
    updateLinkedReportsDisplay();
}

function refreshRelationshipDropdowns() {
    const primarySelect = document.getElementById('relationshipPrimaryReport');
    const foreignSelect = document.getElementById('relationshipForeignReport');
    const addButton = document.getElementById('addRelationshipBtn');

    if (!primarySelect || !foreignSelect) {
        return;
    }

    const prevPrimary = primarySelect.value;
    const prevForeign = foreignSelect.value;

    primarySelect.innerHTML = '<option value="">-- Selecionar relatório --</option>';
    foreignSelect.innerHTML = '<option value="">-- Selecionar relatório --</option>';

    linkedReports.forEach(report => {
        const optionPrimary = document.createElement('option');
        optionPrimary.value = report.report_id;
        optionPrimary.textContent = report.name || `Relatório ${report.report_id}`;
        primarySelect.appendChild(optionPrimary);

        const optionForeign = optionPrimary.cloneNode(true);
        foreignSelect.appendChild(optionForeign);
    });

    if (prevPrimary && [...primarySelect.options].some(opt => opt.value === prevPrimary)) {
        primarySelect.value = prevPrimary;
    }
    if (prevForeign && [...foreignSelect.options].some(opt => opt.value === prevForeign)) {
        foreignSelect.value = prevForeign;
    }

    fillRelationshipFields('primary');
    fillRelationshipFields('foreign');

    if (addButton) {
        addButton.disabled = linkedReports.length < 2;
        addButton.title = linkedReports.length < 2 ? 'Adicione pelo menos dois relatórios para criar relacionamentos.' : '';
    }

    renderRelationshipsCanvas();
}

function fillRelationshipFields(type) {
    const reportSelect = type === 'primary'
        ? document.getElementById('relationshipPrimaryReport')
        : document.getElementById('relationshipForeignReport');
    const fieldSelect = type === 'primary'
        ? document.getElementById('relationshipPrimaryField')
        : document.getElementById('relationshipForeignField');

    if (!reportSelect || !fieldSelect) {
        return;
    }

    const previousValue = fieldSelect.value;
    fieldSelect.innerHTML = '<option value="">-- Selecionar campo --</option>';

    const reportId = parseInt(reportSelect.value);
    if (!reportId || !availableFields[reportId]?.fields) {
        fieldSelect.disabled = true;
        return;
    }

    availableFields[reportId].fields.forEach(fieldName => {
        const option = document.createElement('option');
        option.value = fieldName;
        option.textContent = fieldName;
        fieldSelect.appendChild(option);
    });

    fieldSelect.disabled = false;

    if (previousValue && [...fieldSelect.options].some(opt => opt.value === previousValue)) {
        fieldSelect.value = previousValue;
    }
}

function updateRelationshipsDisplay() {
    const list = document.getElementById('relationshipsList');
    const badge = document.getElementById('relationshipsCount');
    const hiddenInput = document.getElementById('relationshipsConfigInput');

    if (!list) {
        return;
    }

    relationshipCanvasState.listItems = {};

    if (currentRelationshipSelection !== null && (!Array.isArray(relationships) || currentRelationshipSelection >= relationships.length)) {
        currentRelationshipSelection = (relationships && relationships.length > 0) ? relationships.length - 1 : null;
    }

    list.innerHTML = '';

    if (!Array.isArray(relationships) || relationships.length === 0) {
        list.innerHTML = '<div class="list-group-item list-group-item-light"><i class="fas fa-info-circle"></i> Nenhum relacionamento configurado.</div>';
    } else {
        relationships.forEach((rel, index) => {
            const primaryName = getReportName(rel.primary_report_id);
            const foreignName = getReportName(rel.foreign_report_id);
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3';
            item.dataset.relationshipIndex = String(index);
            item.innerHTML = `
                <div>
                    <div class="fw-bold">${primaryName || 'Relatório ' + rel.primary_report_id}<span class="text-muted">.${rel.primary_field}</span>
                        <i class="fas fa-exchange-alt text-muted mx-2"></i>
                        ${foreignName || 'Relatório ' + rel.foreign_report_id}<span class="text-muted">.${rel.foreign_field}</span>
                    </div>
                    <div class="text-muted small mt-1">
                        Tipo: ${rel.relationship_type || 'one_to_many'} |
                        Filtro: ${rel.filter_direction || 'bidirectional'} |
                        Junção: ${rel.join_type || 'inner'}
                        ${rel.active ? '' : ' | <span class="text-danger">Inativo</span>'}
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary edit-relationship-btn" data-index="${index}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger remove-relationship-btn" data-index="${index}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;

            item.addEventListener('click', (event) => {
                if (event.target.closest('button')) {
                    return;
                }
                focusRelationship(index, { scrollToList: false });
            });

            list.appendChild(item);
            relationshipCanvasState.listItems[index] = item;
        });
    }

    if (badge) {
        badge.textContent = Array.isArray(relationships) ? relationships.length : 0;
    }
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(relationships);
    }

    renderRelationshipsCanvas();
    applyRelationshipSelection();
}

function addOrUpdateRelationship() {
    const primaryReportId = parseInt(document.getElementById('relationshipPrimaryReport')?.value || '');
    const primaryField = document.getElementById('relationshipPrimaryField')?.value || '';
    const foreignReportId = parseInt(document.getElementById('relationshipForeignReport')?.value || '');
    const foreignField = document.getElementById('relationshipForeignField')?.value || '';
    const relationshipType = document.getElementById('relationshipType')?.value || 'one_to_many';
    const filterDirection = document.getElementById('relationshipFilterDirection')?.value || 'bidirectional';
    const joinType = document.getElementById('relationshipJoinType')?.value || 'inner';
    const isActive = document.getElementById('relationshipActive')?.checked ?? true;

    if (!primaryReportId || !primaryField || !foreignReportId || !foreignField) {
        alert('❌ Informe relatório e campo em ambos os lados do relacionamento.');
        return;
    }

    if (primaryReportId === foreignReportId && primaryField === foreignField) {
        alert('❌ O relacionamento precisa usar campos diferentes.');
        return;
    }

    const relationshipData = {
        ...(
            relationshipEditIndex >= 0
                ? relationships[relationshipEditIndex] || {}
                : {}
        ),
        primary_report_id: primaryReportId,
        primary_field: primaryField,
        foreign_report_id: foreignReportId,
        foreign_field: foreignField,
        relationship_type: relationshipType,
        filter_direction: filterDirection,
        join_type: joinType,
        active: isActive ? 1 : 0
    };

    let targetIndex;

    if (relationshipEditIndex >= 0) {
        relationships[relationshipEditIndex] = relationshipData;
        targetIndex = relationshipEditIndex;
    } else {
        const duplicate = relationships.find(rel => rel.primary_report_id === relationshipData.primary_report_id &&
            rel.primary_field === relationshipData.primary_field &&
            rel.foreign_report_id === relationshipData.foreign_report_id &&
            rel.foreign_field === relationshipData.foreign_field);
        if (duplicate) {
            alert('⚠️ Já existe um relacionamento configurado com esses campos.');
            return;
        }
        relationships.push(relationshipData);
        targetIndex = relationships.length - 1;
    }

    updateRelationshipsDisplay();
    focusRelationship(targetIndex, { openForm: true, scrollToList: true });
}

function renderRelationshipsCanvas() {
    const canvas = document.getElementById('relationshipsCanvas');
    if (!canvas) {
        return;
    }

    relationshipCanvasState.canvas = canvas;
    canvas.innerHTML = '';

    if (!Array.isArray(linkedReports) || linkedReports.length === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'relationship-placeholder';
        emptyMessage.innerHTML = '<i class="fas fa-info-circle"></i> Adicione relatórios na aba "Fontes de Dados" para habilitar relacionamentos.';
        canvas.appendChild(emptyMessage);
        relationshipCanvasState.svg = null;
        relationshipCanvasState.edgesGroup = null;
        relationshipCanvasState.nodeElements = {};
        return;
    }

    const width = canvas.clientWidth || canvas.offsetWidth || 780;
    const height = Math.max(canvas.clientHeight || canvas.offsetHeight || 420, 360);

    if (!relationshipLayoutMeta.width || Object.keys(relationshipPositions).length !== linkedReports.length) {
        relationshipPositions = computeRelationshipAutoLayout(linkedReports, width, height);
    } else if (relationshipLayoutMeta.width && relationshipLayoutMeta.height && (relationshipLayoutMeta.width !== width || relationshipLayoutMeta.height !== height)) {
        const scaleX = width / relationshipLayoutMeta.width;
        const scaleY = height / relationshipLayoutMeta.height;
        Object.keys(relationshipPositions).forEach((key) => {
            const pos = relationshipPositions[key];
            relationshipPositions[key] = {
                x: clampValue(pos.x * scaleX, RELATIONSHIP_NODE_WIDTH / 2, width - RELATIONSHIP_NODE_WIDTH / 2),
                y: clampValue(pos.y * scaleY, RELATIONSHIP_NODE_HEIGHT / 2, height - RELATIONSHIP_NODE_HEIGHT / 2)
            };
        });
    }

    relationshipLayoutMeta = { width, height };

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.classList.add('relationship-svg');
    svg.setAttribute('width', width);
    svg.setAttribute('height', height);
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);

    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    defs.appendChild(createRelationshipMarker('relationshipArrowEnd', '#0d6efd', false));
    defs.appendChild(createRelationshipMarker('relationshipArrowStart', '#0d6efd', true));
    svg.appendChild(defs);

    const edgesGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    edgesGroup.setAttribute('class', 'relationship-edges-group');
    svg.appendChild(edgesGroup);

    const nodesLayer = document.createElement('div');
    nodesLayer.className = 'relationship-nodes-layer';

    relationshipCanvasState.svg = svg;
    relationshipCanvasState.edgesGroup = edgesGroup;
    relationshipCanvasState.nodeElements = {};
    relationshipCanvasState.edges = [];

    if (!canvas.dataset.relationshipCanvasBound) {
        canvas.addEventListener('click', (event) => {
            if (event.target === canvas || event.target.classList.contains('relationship-nodes-layer')) {
                clearRelationshipSelection();
            }
        });
        canvas.dataset.relationshipCanvasBound = '1';
    }

    linkedReports.forEach((report) => {
        const reportId = report.report_id ?? report.id;
        const position = relationshipPositions[reportId];

        const node = document.createElement('div');
        node.className = 'relationship-node-card' + (report.is_primary ? ' relationship-node-primary' : '');
        node.dataset.reportId = reportId;

        const header = document.createElement('div');
        header.className = 'relationship-node-header';
        const headerIcon = document.createElement('i');
        headerIcon.className = 'fas fa-database';
        const headerText = document.createElement('span');
        headerText.textContent = report.name || `Relatório ${reportId}`;
        header.appendChild(headerIcon);
        header.appendChild(headerText);
        if (report.is_primary) {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary ms-1';
            badge.textContent = 'Principal';
            header.appendChild(badge);
        }

        const body = document.createElement('div');
        body.className = 'relationship-node-body';
        const idInfo = document.createElement('small');
        idInfo.textContent = `ID: ${reportId}`;
        body.appendChild(idInfo);

        if (report.category) {
            const categoryBadge = document.createElement('span');
            categoryBadge.className = 'badge bg-light text-dark mt-1';
            categoryBadge.textContent = report.category;
            body.appendChild(categoryBadge);
        }
        if (report.data_source) {
            const sourceBadge = document.createElement('span');
            sourceBadge.className = 'badge bg-info text-white ms-1 mt-1';
            sourceBadge.textContent = String(report.data_source).toUpperCase();
            body.appendChild(sourceBadge);
        }

        node.appendChild(header);
        node.appendChild(body);

        nodesLayer.appendChild(node);
        relationshipCanvasState.nodeElements[reportId] = node;

        setRelationshipNodePosition(node, position);
        initRelationshipNodeDrag(node, reportId);
    });

    canvas.appendChild(svg);
    canvas.appendChild(nodesLayer);

    updateRelationshipEdges();

    if (!Array.isArray(relationships) || relationships.length === 0) {
        const hint = document.createElement('div');
        hint.className = 'relationship-placeholder relationship-placeholder-on-top';
        hint.innerHTML = '<i class="fas fa-info-circle"></i> Nenhum relacionamento configurado. Use o formulário à direita para criar conexões.';
        canvas.appendChild(hint);
    }
}

function resetRelationshipForm() {
    relationshipEditIndex = -1;
    const primarySelect = document.getElementById('relationshipPrimaryReport');
    const foreignSelect = document.getElementById('relationshipForeignReport');
    if (primarySelect) primarySelect.value = '';
    if (foreignSelect) foreignSelect.value = '';
    fillRelationshipFields('primary');
    fillRelationshipFields('foreign');
    const relTypeSelect = document.getElementById('relationshipType');
    if (relTypeSelect) relTypeSelect.value = 'one_to_many';
    const filterDirectionSelect = document.getElementById('relationshipFilterDirection');
    if (filterDirectionSelect) filterDirectionSelect.value = 'bidirectional';
    const joinTypeSelect = document.getElementById('relationshipJoinType');
    if (joinTypeSelect) joinTypeSelect.value = 'inner';
    const activeCheckbox = document.getElementById('relationshipActive');
    if (activeCheckbox) activeCheckbox.checked = true;
    const addButton = document.getElementById('addRelationshipBtn');
    if (addButton) {
        addButton.textContent = 'Criar Relacionamento';
    }
}

function clearRelationshipSelection() {
    currentRelationshipSelection = null;
    applyRelationshipSelection();
    resetRelationshipForm();
}

function focusRelationship(index, options = {}) {
    const { openForm = true, scrollToList = false } = options;

    if (!Array.isArray(relationships) || index < 0 || index >= relationships.length) {
        return;
    }

    currentRelationshipSelection = index;
    applyRelationshipSelection();

    if (openForm) {
        populateRelationshipForm(index);
    }

    if (scrollToList) {
        const listItem = relationshipCanvasState.listItems[index];
        if (listItem) {
            listItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function applyRelationshipSelection() {
    Object.values(relationshipCanvasState.nodeElements || {}).forEach((node) => {
        node.classList.remove('relationship-node-selected');
    });
    Object.values(relationshipCanvasState.listItems || {}).forEach((item) => {
        item.classList.remove('relationship-list-item-selected');
    });

    (relationshipCanvasState.edges || []).forEach((edge) => {
        edge.line.classList.remove('relationship-edge-selected');
        edge.label.classList.remove('relationship-edge-label-selected');
    });

    if (currentRelationshipSelection === null || currentRelationshipSelection < 0 || currentRelationshipSelection >= relationships.length) {
        return;
    }

    const rel = relationships[currentRelationshipSelection];
    const edgeMeta = (relationshipCanvasState.edges || []).find(e => e.index === currentRelationshipSelection);

    if (edgeMeta) {
        edgeMeta.line.classList.add('relationship-edge-selected');
        edgeMeta.label.classList.add('relationship-edge-label-selected');
    }

    const primaryNode = relationshipCanvasState.nodeElements[rel.primary_report_id];
    const foreignNode = relationshipCanvasState.nodeElements[rel.foreign_report_id];
    if (primaryNode) primaryNode.classList.add('relationship-node-selected');
    if (foreignNode) foreignNode.classList.add('relationship-node-selected');

    const listItem = relationshipCanvasState.listItems[currentRelationshipSelection];
    if (listItem) {
        listItem.classList.add('relationship-list-item-selected');
    }
}

function populateRelationshipForm(index) {
    if (index < 0 || index >= relationships.length) {
        return;
    }

    const rel = relationships[index];
    relationshipEditIndex = index;
    refreshRelationshipDropdowns();

    document.getElementById('relationshipPrimaryReport').value = rel.primary_report_id;
    fillRelationshipFields('primary');
    document.getElementById('relationshipPrimaryField').value = rel.primary_field;

    document.getElementById('relationshipForeignReport').value = rel.foreign_report_id;
    fillRelationshipFields('foreign');
    document.getElementById('relationshipForeignField').value = rel.foreign_field;

    document.getElementById('relationshipType').value = rel.relationship_type || 'one_to_many';
    document.getElementById('relationshipFilterDirection').value = rel.filter_direction || 'bidirectional';
    document.getElementById('relationshipJoinType').value = rel.join_type || 'inner';
    document.getElementById('relationshipActive').checked = !!rel.active;

    const addButton = document.getElementById('addRelationshipBtn');
    if (addButton) {
        addButton.textContent = 'Salvar Relacionamento';
    }
}

function removeRelationship(index) {
    if (index < 0 || index >= relationships.length) {
        return;
    }

    if (!confirm('❌ Remover este relacionamento?')) {
        return;
    }

    relationships.splice(index, 1);

    if (relationships.length === 0) {
        currentRelationshipSelection = null;
    } else if (currentRelationshipSelection !== null) {
        if (currentRelationshipSelection === index) {
            currentRelationshipSelection = null;
        } else if (currentRelationshipSelection > index) {
            currentRelationshipSelection -= 1;
        }
    }

    updateRelationshipsDisplay();

    if (currentRelationshipSelection !== null) {
        focusRelationship(currentRelationshipSelection, { openForm: true, scrollToList: false });
    } else {
        clearRelationshipSelection();
    }
}

function editRelationship(index) {
    focusRelationship(index, { openForm: true, scrollToList: true });
}

function getReportName(reportId) {
    const fromLinked = linkedReports.find(r => r.report_id === parseInt(reportId));
    if (fromLinked) {
        return fromLinked.name || `Relatório ${reportId}`;
    }
    const fromAvailable = availableReports.find(r => r.id === parseInt(reportId));
    return fromAvailable ? fromAvailable.name : `Relatório ${reportId}`;
}

// ========== CAMPOS DISPONÍVEIS ==========

async function loadFieldsFromReports() {
    availableFields = {};
    
    if (linkedReports.length === 0) {
        const fieldsCount = document.getElementById('fieldsCount');
        if (fieldsCount) fieldsCount.textContent = '0';
        return;
    }
    
    const container = document.getElementById('availableFieldsContainer');
    
    // Verificar se o container existe
    if (!container) {
        console.warn('⚠️ Container de campos não encontrado, mas campos serão carregados em memória');
    } else {
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div><p class="mt-2">Carregando campos...</p></div>';
    }
    
    try {
        for (const report of linkedReports) {
            console.log('🔄 Carregando campos do relatório ID:', report.report_id, 'Nome:', report.name);
            const fields = await getFieldsFromReport(report.report_id);
            availableFields[report.report_id] = {
                name: report.name,
                fields: fields
            };
            console.log('✅ Campos carregados:', fields.length, 'campos');
        }
        
        console.log('📊 availableFields completo:', availableFields);
        
        // Só chamar updateFieldsDisplay se container existir
        if (container) {
            updateFieldsDisplay();
        }
        
        updateFieldDropdowns();
        refreshRelationshipDropdowns();
        
    } catch (error) {
        console.error('❌ Erro ao carregar campos:', error);
        if (container) {
            container.innerHTML = '<div class="alert alert-danger">Erro ao carregar campos: ' + error.message + '</div>';
        }
    }
}

async function getFieldsFromReport(reportId) {
    try {
        const formData = new FormData();
        formData.append('report_id', reportId);
        formData.append('limit', '1');
        
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dynamic-report', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            return Object.keys(result.data[0]);
        }
        
        return [];
    } catch (error) {
        console.error('Erro ao buscar campos:', error);
        return [];
    }
}

function updateFieldsDisplay() {
    const container = document.getElementById('availableFieldsContainer');
    
    // Verificar se container existe antes de manipular
    if (!container) {
        console.warn('⚠️ Container availableFieldsContainer não encontrado');
        return;
    }
    
    container.innerHTML = '';
    
    let totalFields = 0;
    
    for (const [reportId, reportData] of Object.entries(availableFields)) {
        const div = document.createElement('div');
        div.className = 'card mb-3';
        
        div.innerHTML = `
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-table text-primary"></i>
                    <strong>${reportData.name}</strong>
                    <span class="badge bg-primary ms-2">${reportData.fields.length} campos</span>
                </h6>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <div class="row g-2">
                    ${reportData.fields.map(field => `
                        <div class="col-md-4">
                            <div class="badge bg-secondary text-start w-100 py-2">
                                <i class="fas fa-database me-1"></i>
                                <small>${field}</small>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        container.appendChild(div);
        totalFields += reportData.fields.length;
    }
    
    const fieldsCount = document.getElementById('fieldsCount');
    if (fieldsCount) {
        fieldsCount.textContent = totalFields;
    }
}

function updateFieldDropdowns() {
    // Popular dropdowns de seleção de relatórios
    populateReportDropdowns();
}

function populateReportDropdowns() {
    // KPI - Popular dropdown de fonte
    const kpiSource = document.getElementById('kpiSource');
    if (kpiSource) {
        kpiSource.innerHTML = '<option value="">-- Escolha --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            kpiSource.appendChild(opt);
        });
    }
    
    // Filtro - Popular dropdown de relatórios
    const filterReportSource = document.getElementById('filterReportSource');
    if (filterReportSource) {
        filterReportSource.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            filterReportSource.appendChild(opt);
        });
    }
    
    // Gráfico - Popular dropdowns de relatórios
    const chartGroupByReport = document.getElementById('chartGroupByReport');
    const chartValueReport = document.getElementById('chartValueReport');
    
    if (chartGroupByReport) {
        chartGroupByReport.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            chartGroupByReport.appendChild(opt);
        });
    }
    
    if (chartValueReport) {
        chartValueReport.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            chartValueReport.appendChild(opt);
        });
    }
}

// ========== SELEÇÃO EM CASCATA (KPI) ==========

function loadKpiFieldsFromSource() {
    const sourceType = document.getElementById('kpiSourceType').value;
    const sourceContainer = document.getElementById('kpiSourceContainer');
    const fieldContainer = document.getElementById('kpiFieldContainer');
    const sourceSelect = document.getElementById('kpiSource');
    
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
        
        sourceSelect.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            sourceSelect.appendChild(opt);
        });
    }
}

function loadKpiFields() {
    const reportId = document.getElementById('kpiSource').value;
    const fieldContainer = document.getElementById('kpiFieldContainer');
    const fieldSelect = document.getElementById('kpiField');
    
    console.log('🔍 loadKpiFields() chamado para reportId:', reportId, 'tipo:', typeof reportId);
    console.log('📊 availableFields atual:', availableFields);
    console.log('📊 Chaves de availableFields:', Object.keys(availableFields));
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    // Tentar com string e número
    let reportData = availableFields[reportId] || availableFields[String(reportId)] || availableFields[Number(reportId)];
    
    console.log('📝 Dados do relatório encontrados:', reportData);
    
    if (reportData && reportData.fields && reportData.fields.length > 0) {
        console.log('✅ Adicionando', reportData.fields.length, 'campos ao dropdown');
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    } else {
        console.warn('⚠️ Nenhum campo encontrado para o relatório ID:', reportId);
        console.warn('   Chaves disponíveis:', Object.keys(availableFields));
        console.warn('   linkedReports:', linkedReports);
        
        // Tentar recarregar os campos
        const report = linkedReports.find(r => r.report_id == reportId);
        if (report) {
            console.log('🔄 Tentando recarregar campos do relatório:', report.name);
            fieldSelect.innerHTML += '<option value="" disabled>⏳ Carregando campos...</option>';
            setTimeout(() => {
                loadFieldsFromReports().then(() => {
                    console.log('✅ Campos recarregados, tentando novamente...');
                    loadKpiFields(); // Tentar novamente após carregar
                });
            }, 100);
        }
    }
}

// ========== SELEÇÃO EM CASCATA (FILTRO) ==========

function populateFilterReportDropdowns() {
    const filterReportSource = document.getElementById('filterReportSource');
    if (!filterReportSource) return;
    
    filterReportSource.innerHTML = '<option value="">-- Escolha o relatório --</option>';
    linkedReports.forEach(report => {
        const opt = document.createElement('option');
        opt.value = report.report_id;
        opt.textContent = report.name;
        filterReportSource.appendChild(opt);
    });
}

function loadFilterFields() {
    const reportId = document.getElementById('filterReportSource').value;
    const fieldContainer = document.getElementById('filterFieldContainer');
    const fieldSelect = document.getElementById('filterField');
    
    console.log('🔍 loadFilterFields() chamado para reportId:', reportId, 'tipo:', typeof reportId);
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    // Tentar com string e número
    const reportData = availableFields[reportId] || availableFields[String(reportId)] || availableFields[Number(reportId)];
    
    console.log('📝 Filtro - Dados encontrados:', reportData ? reportData.fields.length + ' campos' : 'NENHUM');
    
    if (reportData && reportData.fields && reportData.fields.length > 0) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    } else {
        console.warn('⚠️ Nenhum campo de filtro encontrado para reportId:', reportId);
    }
}

// ========== SELEÇÃO EM CASCATA (GRÁFICO) ==========

function populateChartReportDropdowns() {
    const groupByReport = document.getElementById('chartGroupByReport');
    const valueReport = document.getElementById('chartValueReport');
    
    if (groupByReport) {
        groupByReport.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            groupByReport.appendChild(opt);
        });
    }
    
    if (valueReport) {
        valueReport.innerHTML = '<option value="">-- Escolha o relatório --</option>';
        linkedReports.forEach(report => {
            const opt = document.createElement('option');
            opt.value = report.report_id;
            opt.textContent = report.name;
            valueReport.appendChild(opt);
        });
    }
}

function loadChartGroupByFields() {
    const reportId = document.getElementById('chartGroupByReport').value;
    const fieldContainer = document.getElementById('chartGroupByContainer');
    const fieldSelect = document.getElementById('chartGroupBy');
    
    console.log('🔍 loadChartGroupByFields() chamado para reportId:', reportId, 'tipo:', typeof reportId);
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    // Tentar com string e número
    const reportData = availableFields[reportId] || availableFields[String(reportId)] || availableFields[Number(reportId)];
    
    console.log('📝 Chart GroupBy - Dados encontrados:', reportData ? reportData.fields.length + ' campos' : 'NENHUM');
    
    if (reportData && reportData.fields && reportData.fields.length > 0) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    } else {
        console.warn('⚠️ Nenhum campo GroupBy encontrado para reportId:', reportId);
    }
}

function loadChartValueFields() {
    const reportId = document.getElementById('chartValueReport').value;
    const fieldContainer = document.getElementById('chartValueContainer');
    const fieldSelect = document.getElementById('chartValueField');
    
    console.log('🔍 loadChartValueFields() chamado para reportId:', reportId, 'tipo:', typeof reportId);
    
    if (!reportId) {
        fieldContainer.style.display = 'none';
        return;
    }
    
    fieldContainer.style.display = 'block';
    fieldSelect.innerHTML = '<option value="">-- Escolha o campo --</option>';
    
    // Tentar com string e número
    const reportData = availableFields[reportId] || availableFields[String(reportId)] || availableFields[Number(reportId)];
    
    console.log('📝 Chart Value - Dados encontrados:', reportData ? reportData.fields.length + ' campos' : 'NENHUM');
    
    if (reportData && reportData.fields && reportData.fields.length > 0) {
        reportData.fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field;
            opt.textContent = field;
            fieldSelect.appendChild(opt);
        });
    } else {
        console.warn('⚠️ Nenhum campo Value encontrado para reportId:', reportId);
    }
}

// ========== MEDIDAS CALCULADAS ==========

let currentMeasureIndex = -1;

function updateMeasuresDisplay() {
    const container = document.getElementById('measuresContainer');
    
    if (measures.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhuma medida calculada</p>';
    } else {
        container.innerHTML = '';
        measures.forEach((measure, index) => {
            const div = document.createElement('div');
            div.className = 'card mb-2';
            div.innerHTML = `
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-calculator text-secondary"></i>
                                <strong>${measure.name || 'Sem nome'}</strong>
                                <span class="badge bg-info">${measure.format || 'number'}</span>
                            </div>
                            <code class="text-muted small">${measure.formula || 'Sem fórmula'}</code>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-warning" onclick="editMeasure(${index})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeMeasure(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    document.getElementById('measuresConfigInput').value = JSON.stringify(measures);
    document.getElementById('measuresCount').textContent = measures.length;
    
    // Atualizar dropdown de medidas no KPI
    updateMeasuresInKpiDropdown();
}

function updateMeasuresInKpiDropdown() {
    const optgroup = document.getElementById('kpiFieldMeasures');
    if (!optgroup) return;
    
    optgroup.innerHTML = '';
    measures.forEach(measure => {
        const opt = document.createElement('option');
        opt.value = measure.name;
        opt.textContent = measure.name;
        optgroup.appendChild(opt);
    });
}

function openMeasureModal(index = -1) {
    currentMeasureIndex = index;
    
    if (index >= 0) {
        // Editar
        const measure = measures[index];
        document.getElementById('measureModalTitle').textContent = 'Editar Medida';
        document.getElementById('measureName').value = measure.name || '';
        document.getElementById('measureFormula').value = measure.formula || '';
        document.getElementById('measureFormat').value = measure.format || 'number';
        document.getElementById('measureEditIndex').value = index;
    } else {
        // Nova
        document.getElementById('measureModalTitle').textContent = 'Adicionar Medida Calculada';
        document.getElementById('measureName').value = '';
        document.getElementById('measureFormula').value = '';
        document.getElementById('measureFormat').value = 'number';
        document.getElementById('measureEditIndex').value = '-1';
    }
}

function editMeasure(index) {
    openMeasureModal(index);
    new bootstrap.Modal(document.getElementById('measureModal')).show();
}

function saveMeasure() {
    const name = document.getElementById('measureName').value.trim();
    const formula = document.getElementById('measureFormula').value.trim();
    const format = document.getElementById('measureFormat').value;
    const index = parseInt(document.getElementById('measureEditIndex').value);
    
    if (!name || !formula) {
        alert('❌ Nome e fórmula são obrigatórios!');
        return;
    }
    
    const measureData = { name, formula, format };
    
    if (index >= 0) {
        measures[index] = measureData;
        console.log('✏️ Medida editada:', name);
    } else {
        measures.push(measureData);
        console.log('✅ Medida adicionada:', name);
    }
    
    updateMeasuresDisplay();
    bootstrap.Modal.getInstance(document.getElementById('measureModal')).hide();
}

function removeMeasure(index) {
    const measure = measures[index];
    if (confirm(`🗑️ Remover medida "${measure.name}"?`)) {
        measures.splice(index, 1);
        updateMeasuresDisplay();
    }
}

// ========== KPIs ==========

let currentKpiIndex = -1;

function updateKpisDisplay() {
    const container = document.getElementById('kpisContainer');
    
    if (kpis.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhum KPI configurado</p>';
    } else {
        container.innerHTML = '';
        kpis.forEach((kpi, index) => {
            const div = document.createElement('div');
            div.className = 'card mb-2 border-' + (kpi.color || 'primary');
            div.innerHTML = `
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas ${kpi.icon || 'fa-chart-line'} text-${kpi.color || 'primary'}"></i>
                                <strong>${kpi.label || 'Sem rótulo'}</strong>
                                <span class="badge bg-${kpi.color || 'primary'}">${kpi.color || 'primary'}</span>
                            </div>
                            <small class="text-muted">
                                Campo: <code>${kpi.field || 'N/A'}</code>
                                | Agregação: ${kpi.aggregation || 'N/A'}
                                | Formato: ${kpi.format || 'number'}
                            </small>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-warning" onclick="editKpi(${index})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeKpi(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    document.getElementById('kpisConfigInput').value = JSON.stringify(kpis);
    document.getElementById('kpisCount').textContent = kpis.length;
}

function openKpiModal(index = -1) {
    currentKpiIndex = index;
    
    // Popular dropdowns
    populateReportDropdowns();
    
    if (index >= 0) {
        const kpi = kpis[index];
        document.getElementById('kpiModalTitle').textContent = 'Editar KPI';
        
        // Usar dados salvos se disponíveis, senão tentar inferir
        const sourceType = kpi.source_type || (measures.some(m => m.name === kpi.field) ? 'measure' : 'report');
        document.getElementById('kpiSourceType').value = sourceType;
        loadKpiFieldsFromSource();
        
        if (sourceType === 'measure') {
            // Se for medida, preencher diretamente o campo (sem etapa 2)
            setTimeout(() => {
                document.getElementById('kpiField').value = kpi.field || '';
            }, 100);
        } else {
            // Se for relatório, usar o report_id salvo ou tentar encontrar
            const reportId = kpi.source_report_id || (linkedReports.length > 0 ? linkedReports[0].report_id : '');
            if (reportId) {
                document.getElementById('kpiSource').value = reportId;
                
                // Carregar campos e aguardar antes de selecionar
                loadKpiFields();
                
                // Aguardar mais tempo para garantir que os campos foram carregados
                setTimeout(() => {
                    const fieldValue = kpi.field || '';
                    document.getElementById('kpiField').value = fieldValue;
                    console.log('🔍 Tentando selecionar campo KPI:', fieldValue);
                    console.log('📊 Valor selecionado:', document.getElementById('kpiField').value);
                }, 500);
            }
        }
        
        document.getElementById('kpiLabel').value = kpi.label || '';
        document.getElementById('kpiAggregation').value = kpi.aggregation || '';
        document.getElementById('kpiFormat').value = kpi.format || 'number';
        document.getElementById('kpiIcon').value = kpi.icon || 'fa-chart-line';
        document.getElementById('kpiColor').value = kpi.color || 'primary';
        document.getElementById('kpiEditIndex').value = index;
    } else {
        document.getElementById('kpiModalTitle').textContent = 'Adicionar KPI';
        document.getElementById('kpiSourceType').value = '';
        document.getElementById('kpiSource').value = '';
        document.getElementById('kpiField').value = '';
        document.getElementById('kpiLabel').value = '';
        document.getElementById('kpiAggregation').value = 'sum';
        document.getElementById('kpiFormat').value = 'number';
        document.getElementById('kpiIcon').value = 'fa-chart-line';
        document.getElementById('kpiColor').value = 'primary';
        document.getElementById('kpiEditIndex').value = '-1';
        
        // Esconder containers
        document.getElementById('kpiSourceContainer').style.display = 'none';
        document.getElementById('kpiFieldContainer').style.display = 'none';
    }
}

function editKpi(index) {
    openKpiModal(index);
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
    const index = parseInt(document.getElementById('kpiEditIndex').value);
    
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
        source_measure: sourceType === 'measure' ? field : null // Para medida, usar o próprio field
    };
    
    if (index >= 0) {
        kpis[index] = kpiData;
        console.log('✏️ KPI editado:', label);
    } else {
        kpis.push(kpiData);
        console.log('✅ KPI adicionado:', label);
    }
    
    updateKpisDisplay();
    bootstrap.Modal.getInstance(document.getElementById('kpiModal')).hide();
}

function removeKpi(index) {
    if (confirm(`🗑️ Remover KPI "${kpis[index].label}"?`)) {
        kpis.splice(index, 1);
        updateKpisDisplay();
    }
}

// ========== FILTROS ==========

let currentFilterIndex = -1;

function updateFiltersDisplay() {
    const container = document.getElementById('filtersContainer');
    
    if (filters.length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhum filtro configurado</p>';
    } else {
        container.innerHTML = '';
        filters.forEach((filter, index) => {
            const div = document.createElement('div');
            div.className = 'card mb-2';
            div.innerHTML = `
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-filter text-info"></i>
                                <strong>${filter.label || 'Sem rótulo'}</strong>
                                <span class="badge bg-info">${filter.type || 'text'}</span>
                                ${filter.required ? '<span class="badge bg-danger">Obrigatório</span>' : ''}
                            </div>
                            <small class="text-muted">
                                Campo: <code>${filter.field || 'N/A'}</code>
                                ${filter.default_value ? ' | Padrão: ' + filter.default_value : ''}
                                ${filter.filter_report_id ? ' | Usa relatório ID ' + filter.filter_report_id : ''}
                            </small>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-warning" onclick="editFilter(${index})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeFilter(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    document.getElementById('filtersConfigInput').value = JSON.stringify(filters);
    document.getElementById('filtersCount').textContent = filters.length;
}

function openFilterModal(index = -1) {
    currentFilterIndex = index;
    
    // Popular dropdown de relatórios
    populateFilterReportDropdowns();
    
    if (index >= 0) {
        const filter = filters[index];
        document.getElementById('filterModalTitle').textContent = 'Editar Filtro';
        
        // Carregar relatório de origem se disponível
        const reportSource = filter.source_report_id || (linkedReports.length > 0 ? linkedReports[0].report_id : '');
        if (reportSource) {
            document.getElementById('filterReportSource').value = reportSource;
            loadFilterFields();
            
            // Aguardar mais tempo para garantir que os campos foram carregados
            setTimeout(() => {
                const fieldValue = filter.field || '';
                document.getElementById('filterField').value = fieldValue;
                console.log('🔍 Tentando selecionar campo Filtro:', fieldValue);
                console.log('📊 Valor selecionado:', document.getElementById('filterField').value);
            }, 500);
        } else {
            document.getElementById('filterField').value = filter.field || '';
        }
        
        document.getElementById('filterLabel').value = filter.label || '';
        document.getElementById('filterType').value = filter.type || 'text';
        document.getElementById('filterDefaultValue').value = filter.default_value || '';
        document.getElementById('filterRequired').checked = filter.required || false;
        document.getElementById('filterReportId').value = filter.filter_report_id || '';
        document.getElementById('filterEditIndex').value = index;
    } else {
        document.getElementById('filterModalTitle').textContent = 'Adicionar Filtro';
        document.getElementById('filterReportSource').value = '';
        document.getElementById('filterField').value = '';
        document.getElementById('filterLabel').value = '';
        document.getElementById('filterType').value = 'text';
        document.getElementById('filterDefaultValue').value = '';
        document.getElementById('filterRequired').checked = false;
        document.getElementById('filterReportId').value = '';
        document.getElementById('filterEditIndex').value = '-1';
        
        // Esconder container de campo
        document.getElementById('filterFieldContainer').style.display = 'none';
    }
}

function editFilter(index) {
    openFilterModal(index);
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
    const index = parseInt(document.getElementById('filterEditIndex').value);
    
    console.log('💾 saveFilter() - Dados capturados:');
    console.log('   field:', field);
    console.log('   label:', label);
    console.log('   type:', type);
    console.log('   filterReportId:', filterReportId);
    console.log('   sourceReportId:', sourceReportId);
    
    if (!field) {
        alert('❌ Selecione um campo!');
        return;
    }
    
    if (!label) {
        alert('❌ Rótulo é obrigatório!');
        return;
    }
    
    if (!type) {
        alert('❌ Tipo de filtro é obrigatório!');
        return;
    }
    
    // Determinar source_field baseado no relatório selecionado
    let sourceField = field;
    
    // Se há filterReportId, buscar o campo do relatório de filtro
    if (filterReportId) {
        const reportIdInt = parseInt(filterReportId);
        const reportData = availableFields[reportIdInt] || availableFields[String(reportIdInt)];
        
        if (reportData && reportData.fields) {
            // O campo selecionado no dropdown já é do relatório de filtro
            sourceField = field;
            console.log('   ✅ source_field extraído do relatório de filtro:', sourceField);
        }
    }
    
    const filterData = { 
        field,
        label, 
        type, 
        default_value: defaultValue,
        required: required,
        filter_report_id: filterReportId ? parseInt(filterReportId) : null,
        source_field: sourceField,
        source_report_id: sourceReportId ? parseInt(sourceReportId) : null
    };
    
    console.log('💾 Dados finais do filtro:', filterData);
    
    if (index >= 0) {
        filters[index] = filterData;
        console.log('✏️ Filtro editado:', label);
    } else {
        filters.push(filterData);
        console.log('✅ Filtro adicionado:', label);
    }
    
    updateFiltersDisplay();
    bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
}

function removeFilter(index) {
    if (confirm(`🗑️ Remover filtro "${filters[index].label}"?`)) {
        filters.splice(index, 1);
        updateFiltersDisplay();
    }
}

// ========== GRÁFICOS ==========

let currentChartKey = '';

function updateChartsDisplay() {
    const container = document.getElementById('chartsContainer');
    
    if (Object.keys(charts).length === 0) {
        container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> Nenhum gráfico configurado</p>';
    } else {
        container.innerHTML = '';
        let chartIndex = 0;
        for (const [key, chart] of Object.entries(charts)) {
            const div = document.createElement('div');
            div.className = 'card mb-2';
            div.innerHTML = `
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-chart-bar text-warning"></i>
                                <strong>${chart.title || 'Gráfico ' + (chartIndex + 1)}</strong>
                                <span class="badge bg-warning text-dark">${chart.type || 'bar'}</span>
                            </div>
                            <small class="text-muted">
                                Agrupar: <code>${chart.group_by || 'N/A'}</code>
                                | Valor: <code>${chart.value_field || 'N/A'}</code>
                                | Agregação: ${chart.aggregation || 'sum'}
                            </small>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-warning" onclick="editChart('${key}')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeChart('${key}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(div);
            chartIndex++;
        }
    }
    
    document.getElementById('chartsConfigInput').value = JSON.stringify(charts);
    document.getElementById('chartsCount').textContent = Object.keys(charts).length;
}

function openChartModal(key = '') {
    currentChartKey = key;
    
    // Popular dropdowns de relatórios
    populateChartReportDropdowns();
    
    if (key && charts[key]) {
        const chart = charts[key];
        document.getElementById('chartModalTitle').textContent = 'Editar Gráfico';
        document.getElementById('chartType').value = chart.type || 'bar';
        document.getElementById('chartTitle').value = chart.title || '';
        
        // Carregar relatórios de origem se disponíveis
        const groupByReport = chart.group_by_report_id || (linkedReports.length > 0 ? linkedReports[0].report_id : '');
        const valueReport = chart.value_report_id || (linkedReports.length > 0 ? linkedReports[0].report_id : '');
        
        if (groupByReport) {
            document.getElementById('chartGroupByReport').value = groupByReport;
            loadChartGroupByFields();
            
            // Aguardar mais tempo para garantir que os campos foram carregados
            setTimeout(() => {
                const fieldValue = chart.group_by || '';
                document.getElementById('chartGroupBy').value = fieldValue;
                console.log('🔍 Tentando selecionar campo Gráfico GroupBy:', fieldValue);
                console.log('📊 Valor selecionado:', document.getElementById('chartGroupBy').value);
            }, 500);
        } else {
            document.getElementById('chartGroupBy').value = chart.group_by || '';
        }
        
        if (valueReport) {
            document.getElementById('chartValueReport').value = valueReport;
            loadChartValueFields();
            
            // Aguardar mais tempo para garantir que os campos foram carregados
            setTimeout(() => {
                const fieldValue = chart.value_field || '';
                document.getElementById('chartValueField').value = fieldValue;
                console.log('🔍 Tentando selecionar campo Gráfico ValueField:', fieldValue);
                console.log('📊 Valor selecionado:', document.getElementById('chartValueField').value);
            }, 500);
        } else {
            document.getElementById('chartValueField').value = chart.value_field || '';
        }
        
        document.getElementById('chartAggregation').value = chart.aggregation || 'sum';
        document.getElementById('chartColor').value = chart.color || '#4CAF50';
        document.getElementById('chartEditKey').value = key;
    } else {
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
        
        // Esconder containers
        document.getElementById('chartGroupByContainer').style.display = 'none';
        document.getElementById('chartValueContainer').style.display = 'none';
    }
}

function editChart(key) {
    openChartModal(key);
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
    const editKey = document.getElementById('chartEditKey').value;
    
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
    
    if (editKey) {
        charts[editKey] = chartData;
        console.log('✏️ Gráfico editado:', title);
    } else {
        const newKey = 'chart' + (Object.keys(charts).length + 1);
        charts[newKey] = chartData;
        console.log('✅ Gráfico adicionado:', title);
    }
    
    updateChartsDisplay();
    bootstrap.Modal.getInstance(document.getElementById('chartModal')).hide();
}

function removeChart(key) {
    if (confirm(`🗑️ Remover gráfico "${charts[key].title}"?`)) {
        delete charts[key];
        updateChartsDisplay();
    }
}

// Log ANTES do submit para garantir que todos os campos estão preenchidos
document.getElementById('dashboardForm').addEventListener('submit', function(e) {
    const dashboardIdInput = document.getElementById('dashboardIdInput');
    const dashboardIdValue = dashboardIdInput ? dashboardIdInput.value : 'NÃO ENCONTRADO';
    
    // Pegar valores dos campos hidden
    const measuresValue = document.getElementById('measuresConfigInput').value;
    const kpisValue = document.getElementById('kpisConfigInput').value;
    const filtersValue = document.getElementById('filtersConfigInput').value;
    const chartsValue = document.getElementById('chartsConfigInput').value;
    const reportIdsValue = document.getElementById('reportIdsInput').value;
    
    console.log('========================================');
    console.log('🚀 FORM SUBMIT - VERIFICAÇÃO COMPLETA');
    console.log('========================================');
    console.log('dashboard_id:', dashboardIdValue);
    console.log('measures_config:', measuresValue.substring(0, 100) + '...');
    console.log('kpis_config:', kpisValue.substring(0, 100) + '...');
    console.log('filters_config:', filtersValue.substring(0, 100) + '...');
    console.log('charts_config:', chartsValue.substring(0, 100) + '...');
    console.log('report_ids:', reportIdsValue);
    console.log('========================================');
    console.log('TAMANHOS:');
    console.log('   measures_config:', measuresValue.length, 'bytes');
    console.log('   kpis_config:', kpisValue.length, 'bytes');
    console.log('   filters_config:', filtersValue.length, 'bytes');
    console.log('   charts_config:', chartsValue.length, 'bytes');
    console.log('========================================');
    
    // Verificar se o valor está vazio ou 0
    if (!dashboardIdValue || dashboardIdValue === '0' || dashboardIdValue === '') {
        console.error('❌ ERRO: dashboard_id está vazio ou 0! Cancelando submit.');
        e.preventDefault();
        alert('Erro: ID do dashboard não encontrado. Recarregue a página e tente novamente.');
        return false;
    }
    
    // Alertar se campos estão vazios
    if (measuresValue === '[]' || measuresValue === '') {
        console.warn('⚠️ ATENÇÃO: measures_config está vazio!');
    }
    if (kpisValue === '[]' || kpisValue === '') {
        console.warn('⚠️ ATENÇÃO: kpis_config está vazio!');
    }
    
    console.log('✅ Enviando formulário...');
});

const relationshipCanvasState = { canvas: null, svg: null, edgesGroup: null, nodeElements: {} };
let relationshipRenderTimer = null;

window.addEventListener('resize', () => {
    if (relationshipRenderTimer) {
        clearTimeout(relationshipRenderTimer);
    }
    relationshipRenderTimer = setTimeout(() => {
        renderRelationshipsCanvas();
    }, 200);
});

function computeRelationshipAutoLayout(reports, width, height) {
    const positions = {};
    const total = reports.length;

    if (total === 1) {
        const reportId = reports[0].report_id ?? reports[0].id;
        positions[reportId] = {
            x: width / 2,
            y: height / 2
        };
        return positions;
    }

    const radius = Math.max(Math.min(width, height) / 2 - Math.max(RELATIONSHIP_NODE_WIDTH, RELATIONSHIP_NODE_HEIGHT), 120);
    const centerX = width / 2;
    const centerY = height / 2;

    reports.forEach((report, index) => {
        const reportId = report.report_id ?? report.id;
        const angle = (index / total) * (Math.PI * 2);
        const x = centerX + radius * Math.cos(angle);
        const y = centerY + radius * Math.sin(angle);
        positions[reportId] = {
            x: clampValue(x, RELATIONSHIP_NODE_WIDTH / 2, width - RELATIONSHIP_NODE_WIDTH / 2),
            y: clampValue(y, RELATIONSHIP_NODE_HEIGHT / 2, height - RELATIONSHIP_NODE_HEIGHT / 2)
        };
    });

    return positions;
}

function clampValue(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function createRelationshipMarker(id, color, reverse = false) {
    const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
    marker.setAttribute('id', id);
    marker.setAttribute('markerWidth', '12');
    marker.setAttribute('markerHeight', '12');
    marker.setAttribute('refX', reverse ? '0' : '12');
    marker.setAttribute('refY', '6');
    marker.setAttribute('orient', 'auto');
    marker.setAttribute('markerUnits', 'strokeWidth');

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', reverse ? 'M12,0 L0,6 L12,12' : 'M0,0 L12,6 L0,12');
    path.setAttribute('fill', color);

    marker.appendChild(path);
    return marker;
}

function setRelationshipNodePosition(node, position) {
    if (!position) {
        return;
    }
    node.style.left = `${position.x - RELATIONSHIP_NODE_WIDTH / 2}px`;
    node.style.top = `${position.y - RELATIONSHIP_NODE_HEIGHT / 2}px`;
}

function initRelationshipNodeDrag(node, reportId) {
    node.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        node.setPointerCapture(event.pointerId);
        node.classList.add('relationship-node-dragging');

        const startPosition = { ...relationshipPositions[reportId] };
        const startX = event.clientX;
        const startY = event.clientY;

        const onPointerMove = (moveEvent) => {
            const deltaX = moveEvent.clientX - startX;
            const deltaY = moveEvent.clientY - startY;
            const width = relationshipLayoutMeta.width || (relationshipCanvasState.canvas?.clientWidth ?? 0);
            const height = relationshipLayoutMeta.height || (relationshipCanvasState.canvas?.clientHeight ?? 0);

            relationshipPositions[reportId] = {
                x: clampValue(startPosition.x + deltaX, RELATIONSHIP_NODE_WIDTH / 2, width - RELATIONSHIP_NODE_WIDTH / 2),
                y: clampValue(startPosition.y + deltaY, RELATIONSHIP_NODE_HEIGHT / 2, height - RELATIONSHIP_NODE_HEIGHT / 2)
            };

            setRelationshipNodePosition(node, relationshipPositions[reportId]);
            updateRelationshipEdges();
        };

        const onPointerUp = () => {
            node.classList.remove('relationship-node-dragging');
            node.releasePointerCapture(event.pointerId);
            node.removeEventListener('pointermove', onPointerMove);
            node.removeEventListener('pointerup', onPointerUp);
            node.removeEventListener('pointercancel', onPointerUp);
        };

        node.addEventListener('pointermove', onPointerMove);
        node.addEventListener('pointerup', onPointerUp);
        node.addEventListener('pointercancel', onPointerUp);
    });
}

function getRelationshipCardinalityLabel(type) {
    switch ((type || '').toLowerCase()) {
        case 'one_to_many':
            return '1 : N';
        case 'many_to_one':
            return 'N : 1';
        case 'many_to_many':
            return 'N : N';
        default:
            return type || '1 : N';
    }
}

function updateRelationshipEdges() {
    if (!relationshipCanvasState.svg || !relationshipCanvasState.edgesGroup) {
        return;
    }

    const edgesGroup = relationshipCanvasState.edgesGroup;
    while (edgesGroup.firstChild) {
        edgesGroup.removeChild(edgesGroup.firstChild);
    }

    relationshipCanvasState.edges = [];

    if (!Array.isArray(relationships) || relationships.length === 0) {
        return;
    }

    relationships.forEach((relationship, index) => {
        const primaryId = relationship.primary_report_id;
        const foreignId = relationship.foreign_report_id;
        const primaryPos = relationshipPositions[primaryId];
        const foreignPos = relationshipPositions[foreignId];

        if (!primaryPos || !foreignPos) {
            return;
        }

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('class', 'relationship-edge');
        line.setAttribute('x1', primaryPos.x);
        line.setAttribute('y1', primaryPos.y);
        line.setAttribute('x2', foreignPos.x);
        line.setAttribute('y2', foreignPos.y);
        line.setAttribute('stroke', '#0d6efd');
        line.setAttribute('stroke-width', '2');
        line.dataset.relationshipIndex = String(index);

        const direction = (relationship.filter_direction || 'bidirectional').toLowerCase();
        if (direction === 'primary_to_foreign') {
            line.setAttribute('marker-end', 'url(#relationshipArrowEnd)');
        } else if (direction === 'foreign_to_primary') {
            line.setAttribute('marker-start', 'url(#relationshipArrowStart)');
        } else {
            line.setAttribute('marker-start', 'url(#relationshipArrowStart)');
            line.setAttribute('marker-end', 'url(#relationshipArrowEnd)');
        }

        const focusHandler = (event) => {
            event.stopPropagation();
            focusRelationship(index, { scrollToList: true });
        };

        line.addEventListener('click', focusHandler);
        line.addEventListener('pointerdown', (event) => event.stopPropagation());
        edgesGroup.appendChild(line);

        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('class', 'relationship-edge-label');
        text.setAttribute('text-anchor', 'middle');
        text.style.pointerEvents = 'auto';
        text.style.cursor = 'pointer';
        text.dataset.relationshipIndex = String(index);

        const midX = (primaryPos.x + foreignPos.x) / 2;
        const midY = (primaryPos.y + foreignPos.y) / 2;
        text.setAttribute('x', midX);
        text.setAttribute('y', midY - 8);

        const cardinalityLabel = getRelationshipCardinalityLabel(relationship.relationship_type);
        const joinLabel = (relationship.join_type || 'INNER').toUpperCase();
        let directionSymbol = '↔';
        if (direction === 'primary_to_foreign') {
            directionSymbol = '→';
        } else if (direction === 'foreign_to_primary') {
            directionSymbol = '←';
        }

        text.textContent = `${directionSymbol} ${cardinalityLabel} · ${joinLabel}`;
        text.addEventListener('click', focusHandler);
        text.addEventListener('pointerdown', (event) => event.stopPropagation());
        edgesGroup.appendChild(text);

        relationshipCanvasState.edges.push({
            index,
            line,
            label: text,
            primaryId,
            foreignId
        });
    });

    applyRelationshipSelection();
}

</script>

<style>
.sticky-bottom {
    position: sticky;
    bottom: 0;
    z-index: 1020;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.form-control-color {
    width: 100%;
    height: 38px;
}

.badge {
    font-size: 0.75rem;
}

.relationship-canvas {
    position: relative;
    min-height: 360px;
    border: 1px solid #e0e3ef;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 25%, transparent 25%) -10px 0/20px 20px,
                linear-gradient(225deg, rgba(13, 110, 253, 0.05) 25%, transparent 25%) -10px 0/20px 20px,
                linear-gradient(45deg, rgba(13, 110, 253, 0.05) 25%, transparent 25%) 0 0/20px 20px,
                linear-gradient(315deg, rgba(13, 110, 253, 0.05) 25%, #ffffff 25%) 0 0/20px 20px;
    overflow: hidden;
}

.relationship-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.relationship-nodes-layer {
    position: absolute;
    inset: 0;
}

.relationship-node-card {
    position: absolute;
    width: 220px;
    min-height: 140px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
    border: 1px solid rgba(13, 110, 253, 0.12);
    padding: 12px 14px;
    cursor: grab;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
    user-select: none;
}

.relationship-node-card.relationship-node-dragging {
    cursor: grabbing;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.22);
    transform: scale(1.02);
}

.relationship-node-primary {
    border: 1px solid rgba(13, 110, 253, 0.35);
}

.relationship-node-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #0d6efd;
    margin-bottom: 4px;
}

.relationship-node-header i {
    font-size: 1rem;
}

.relationship-node-body {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 0.8rem;
    color: #495057;
}

.relationship-node-body .badge {
    align-self: flex-start;
}

.relationship-placeholder {
    position: absolute;
    top: 12px;
    left: 12px;
    right: 12px;
    background: rgba(248, 249, 250, 0.95);
    border: 1px dashed rgba(13, 110, 253, 0.25);
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    color: #6c757d;
    font-style: italic;
    pointer-events: none;
}

.relationship-placeholder-on-top {
    bottom: 12px;
    top: auto;
}

.relationship-edges-group .relationship-edge {
    pointer-events: stroke;
    cursor: pointer;
}

.relationship-edge-label {
    fill: #0d6efd;
    font-size: 0.7rem;
    font-weight: 600;
    text-shadow: 0 0 4px #ffffff;
    pointer-events: auto;
    cursor: pointer;
}

.relationship-edge-selected {
    stroke: #fd7e14 !important;
}

.relationship-edge-label-selected {
    fill: #fd7e14 !important;
}

.relationship-node-selected {
    box-shadow: 0 14px 28px rgba(253, 126, 20, 0.35);
    border-color: rgba(253, 126, 20, 0.55);
}

.relationship-list-item-selected {
    border-left: 4px solid #fd7e14;
    background: rgba(253, 126, 20, 0.08);
}

.relationship-links-list {
    list-style: none;
    margin: 8px 0 0;
    padding: 0;
    font-size: 0.75rem;
    color: #6c757d;
}

.relationship-links-list li {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}
</style>
