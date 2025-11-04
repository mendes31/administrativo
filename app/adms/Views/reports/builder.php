<?php
$report = $this->data['report'] ?? null;
$availableTables = $this->data['availableTables'] ?? [];
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">
                <i class="fas fa-chart-bar"></i>
                <?= $report ? 'Editar Relatório' : 'Construtor de Relatórios Dinâmicos' ?>
            </h3>
        </div>
        <div class="card-body">
            <!-- Abas de Modo -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="builder-tab" data-bs-toggle="tab" data-bs-target="#builder-mode" 
                            type="button" role="tab">
                        <i class="fas fa-cubes"></i> Modo Construtor Visual (Tabelas Locais)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="sql-tab" data-bs-toggle="tab" data-bs-target="#sql-mode" 
                            type="button" role="tab">
                        <i class="fas fa-code"></i> SQL Personalizado (Local + SAP B1)
                    </button>
                </li>
            </ul>

            <form id="reportBuilderForm" method="POST" action="<?= $_ENV['URL_ADM'] ?>save-dynamic-report">
                <input type="hidden" name="id" value="<?= $report['id'] ?? '' ?>">
                <input type="hidden" name="query_mode" id="queryMode" value="builder">
                
                <!-- Informações Básicas (sempre visível) -->
                <div class="row mb-4">
                    <div class="col-md-9">
                        <label class="form-label fw-bold">Nome do Relatório *</label>
                        <input type="text" name="name" id="reportName" class="form-control" 
                               value="<?= htmlspecialchars($report['name'] ?? '') ?>" 
                               placeholder="Ex: Vendas do Mês" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Categoria</label>
                        <input type="text" name="category" class="form-control" 
                               value="<?= htmlspecialchars($report['category'] ?? '') ?>"
                               placeholder="Ex: Vendas">
                    </div>
                </div>

                <!-- Conteúdo das Abas -->
                <div class="tab-content">
                    <!-- ABA 1: MODO CONSTRUTOR -->
                    <div class="tab-pane fade show active" id="builder-mode" role="tabpanel">
                        <div class="row">
                            <!-- Coluna Esquerda: Tabelas e Campos -->
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-database"></i> Tabelas e Campos</h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <!-- Busca de Tabelas -->
                                        <input type="text" id="tableSearch" class="form-control form-control-sm mb-2" 
                                               placeholder="🔍 Buscar tabela...">
                                        
                                        <!-- Lista de Tabelas -->
                                        <div id="tablesList" style="max-height: 500px; overflow-y: auto;">
                                            <?php foreach ($availableTables as $tableName => $tableInfo): ?>
                                                <div class="table-item p-2 border-bottom" data-table="<?= $tableName ?>" 
                                                     style="cursor: pointer;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-table text-primary"></i>
                                                            <strong><?= htmlspecialchars($tableName) ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($tableInfo['label']) ?></small>
                                                        </div>
                                                        <span class="badge bg-<?= ($tableInfo['connection'] ?? 'local') === 'sap_b1' ? 'warning' : 'success' ?>">
                                                            <?= ($tableInfo['connection'] ?? 'local') === 'sap_b1' ? 'SAP B1' : 'Local' ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- Campos da Tabela (ocultos inicialmente) -->
                                                    <div class="fields-list mt-2" style="display: none;">
                                                        <div class="list-group list-group-flush">
                                                            <?php foreach ($tableInfo['fields'] as $fieldName => $fieldLabel): ?>
                                                                <div class="list-group-item list-group-item-action p-1 field-item" 
                                                                     data-field="<?= $fieldName ?>"
                                                                     style="cursor: pointer; font-size: 0.85rem;">
                                                                    <i class="fas fa-columns"></i>
                                                                    <?= htmlspecialchars($fieldName) ?>
                                                                    <br>
                                                                    <small class="text-muted"><?= htmlspecialchars($fieldLabel) ?></small>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Coluna Direita: Query Builder -->
                            <div class="col-md-8">
                                <input type="hidden" name="data_source" id="dataSource" value="<?= $report['data_source'] ?? '' ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tabela Selecionada:</label>
                                    <div class="input-group">
                                        <input type="text" id="selectedTableDisplay" class="form-control" readonly 
                                               value="<?= $report['data_source'] ?? 'Nenhuma tabela selecionada' ?>">
                                        <button type="button" class="btn btn-outline-secondary" id="clearTableBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Campos Selecionados:</label>
                                    <div id="selectedFieldsContainer" class="border rounded p-3 min-height-100" 
                                         style="min-height: 150px; background: #f8f9fa;">
                                        <p class="text-muted text-center my-5">
                                            <i class="fas fa-hand-point-left"></i> 
                                            Clique nos campos à esquerda para adicionar
                                            <br>
                                            <small>Ou deixe vazio para SELECT * FROM tabela</small>
                                        </p>
                                    </div>
                                </div>

                                <!-- FILTROS (WHERE) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-filter"></i> Filtros (WHERE):
                                    </label>
                                    <div id="filtersContainer" class="border rounded p-3" style="min-height: 80px; background: #f8f9fa;">
                                        <p class="text-muted text-center m-0" id="noFilters">
                                            <small><i class="fas fa-info-circle"></i> Nenhum filtro adicionado</small>
                                        </p>
                                        <div id="filtersList"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addFilterBtn">
                                        <i class="fas fa-plus"></i> Adicionar Filtro
                                    </button>
                                </div>

                                <!-- ORDENAÇÃO (ORDER BY) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-sort"></i> Ordenar Por (ORDER BY):
                                    </label>
                                    <div id="orderByContainer" class="border rounded p-3" style="min-height: 80px; background: #f8f9fa;">
                                        <p class="text-muted text-center m-0" id="noOrderBy">
                                            <small><i class="fas fa-info-circle"></i> Nenhuma ordenação adicionada</small>
                                        </p>
                                        <div id="orderByList"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addOrderByBtn">
                                        <i class="fas fa-plus"></i> Adicionar Ordenação
                                    </button>
                                </div>

                                <!-- AGRUPAMENTO (GROUP BY) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-layer-group"></i> Agrupar Por (GROUP BY):
                                    </label>
                                    <div id="groupByContainer" class="border rounded p-3" style="min-height: 80px; background: #f8f9fa;">
                                        <p class="text-muted text-center m-0" id="noGroupBy">
                                            <small><i class="fas fa-info-circle"></i> Nenhum agrupamento adicionado</small>
                                        </p>
                                        <div id="groupByList"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addGroupByBtn">
                                        <i class="fas fa-plus"></i> Adicionar Agrupamento
                                    </button>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tipo de Visualização:</label>
                                        <select name="visualization_type" class="form-select" id="visualizationType">
                                            <option value="table" selected>📊 Tabela</option>
                                            <option value="bar_chart">📈 Gráfico de Barras</option>
                                            <option value="line_chart">📉 Gráfico de Linhas</option>
                                            <option value="pie_chart">🥧 Gráfico de Pizza</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_public" id="isPublic">
                                            <label class="form-check-label" for="isPublic">
                                                Compartilhar com outros usuários
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA 2: MODO SQL PERSONALIZADO -->
                    <div class="tab-pane fade" id="sql-mode" role="tabpanel">
                        <div class="alert alert-primary border-primary">
                            <h5><i class="fas fa-code"></i> Modo SQL Personalizado</h5>
                            <p class="mb-2">
                                <strong>Escreva sua query SQL diretamente.</strong> 
                                O sistema detecta automaticamente se é <span class="badge bg-success">Local</span> ou <span class="badge bg-warning">SAP B1</span>.
                            </p>
                            <hr>
                            <p class="mb-0 small">
                                <i class="fas fa-lightbulb text-warning"></i> 
                                <strong>Exemplos:</strong>
                            </p>
                            <ul class="small mb-0">
                                <li><code>SELECT * FROM adms_users</code> → Todos os usuários (Local)</li>
                                <li><code>SELECT name, email FROM adms_users WHERE status = 'Ativo'</code> → Usuários ativos (Local)</li>
                                <li><code>SELECT * FROM OITM</code> → Todos os itens (SAP B1) 🔷</li>
                                <li><code>SELECT CardCode, CardName, Balance FROM OCRD WHERE CardType = 'C'</code> → Clientes (SAP B1) 🔷</li>
                                <li><code>SELECT DocNum, DocDate, DocTotal FROM OINV WHERE MONTH(DocDate) = MONTH(CURRENT_DATE)</code> → NFs do mês (SAP B1) 🔷</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-edit"></i> Query SQL:
                            </label>
                            <textarea name="custom_sql" id="customSql" class="form-control font-monospace border-primary" 
                                      rows="12" placeholder="Digite sua query SQL aqui...&#10;&#10;Exemplo:&#10;SELECT * FROM OITM&#10;&#10;ou&#10;&#10;SELECT ItemCode, ItemName, OnHand &#10;FROM OITM&#10;WHERE OnHand > 0&#10;ORDER BY ItemName"
                                      style="font-size: 14px; background-color: #f8f9fa;"><?= htmlspecialchars($report['custom_sql'] ?? '') ?></textarea>
                            <div class="form-text">
                                <i class="fas fa-shield-alt text-success"></i> 
                                Apenas queries <strong>SELECT</strong> são permitidas por segurança.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de Visualização:</label>
                                <select name="visualization_type_sql" class="form-select">
                                    <option value="table">📊 Tabela</option>
                                    <option value="bar_chart">📈 Gráfico de Barras</option>
                                    <option value="line_chart">📉 Gráfico de Linhas</option>
                                    <option value="pie_chart">🥧 Gráfico de Pizza</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0 mt-4">
                                    <div class="card-body p-2">
                                        <small class="text-muted">
                                            <i class="fas fa-magic"></i> <strong>Auto-detecção:</strong>
                                            <br>
                                            Tabelas SAP B1: OCRD, OINV, ORDR, OITM, etc → <span class="badge bg-warning">SAP B1</span>
                                            <br>
                                            Outras tabelas → <span class="badge bg-success">Local</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs -->
                <input type="hidden" name="fields" id="fieldsJson" value="[]">
                <input type="hidden" name="filters" id="filtersJson" value="[]">
                <input type="hidden" name="groupby" id="groupbyJson" value="[]">
                <input type="hidden" name="orderby" id="orderbyJson" value="[]">
                <input type="hidden" name="chart_config" id="chartConfigJson" value="{}">

                <hr class="my-4">

                <!-- Botões de Ação -->
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-info btn-lg" id="previewBtn">
                            <i class="fas fa-eye"></i> Visualizar Prévia em Tempo Real
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Salvar Relatório
                        </button>
                        <a href="<?= $_ENV['URL_ADM'] ?>list-dynamic-reports" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Card de Prévia -->
    <div class="card mt-4" id="previewCard" style="display: none;">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-eye"></i> Prévia do Relatório</h5>
        </div>
        <div class="card-body">
            <div id="previewContent"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let reportState = {
    dataSource: '<?= $report['data_source'] ?? '' ?>',
    availableFields: {},
    selectedFields: [],
    filters: [],
    orderBy: [],
    groupBy: [],
    connectionType: 'local',
    queryMode: 'builder'
};

document.addEventListener('DOMContentLoaded', function() {
    // Busca de tabelas
    document.getElementById('tableSearch').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.table-item').forEach(item => {
            const tableName = item.dataset.table.toLowerCase();
            const tableLabel = item.querySelector('small').textContent.toLowerCase();
            item.style.display = (tableName.includes(search) || tableLabel.includes(search)) ? 'block' : 'none';
        });
    });

    // Clique em tabela
    document.querySelectorAll('.table-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.classList.contains('field-item') || e.target.closest('.field-item')) return;
            
            const tableName = this.dataset.table;
            const fieldsDiv = this.querySelector('.fields-list');
            const fieldItems = fieldsDiv ? fieldsDiv.querySelectorAll('.field-item') : [];
            
            console.log(`📋 Clicou na tabela: ${tableName}`);
            console.log(`  Campos encontrados: ${fieldItems.length}`);
            
            if (fieldsDiv) {
                const isVisible = fieldsDiv.style.display !== 'none';
                fieldsDiv.style.display = isVisible ? 'none' : 'block';
                console.log(`  Status: ${isVisible ? 'Fechando' : 'Abrindo'} lista de campos`);
            } else {
                console.warn(`  ❌ Div .fields-list não encontrada!`);
            }
            
            // Selecionar tabela
            reportState.dataSource = tableName;
            document.getElementById('dataSource').value = tableName;
            document.getElementById('selectedTableDisplay').value = tableName;
        });
    });

    // Clique em campo
    document.querySelectorAll('.field-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const fieldName = this.dataset.field;
            const fieldLabel = this.querySelector('small') ? this.querySelector('small').textContent : fieldName;
            addSelectedField({field: fieldName, label: fieldLabel});
        });
    });

    // Limpar tabela
    document.getElementById('clearTableBtn').addEventListener('click', function() {
        reportState.dataSource = '';
        reportState.selectedFields = [];
        reportState.filters = [];
        reportState.orderBy = [];
        reportState.groupBy = [];
        document.getElementById('dataSource').value = '';
        document.getElementById('selectedTableDisplay').value = 'Nenhuma tabela selecionada';
        document.getElementById('selectedFieldsContainer').innerHTML = `
            <p class="text-muted text-center my-5">
                <i class="fas fa-hand-point-left"></i> Clique nos campos à esquerda para adicionar
            </p>
        `;
        updateFiltersDisplay();
        updateOrderByDisplay();
        updateGroupByDisplay();
    });

    // Botões de adicionar
    document.getElementById('addFilterBtn').addEventListener('click', addFilter);
    document.getElementById('addOrderByBtn').addEventListener('click', addOrderBy);
    document.getElementById('addGroupByBtn').addEventListener('click', addGroupBy);

    // Troca de aba
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            reportState.queryMode = e.target.id === 'sql-tab' ? 'custom_sql' : 'builder';
            document.getElementById('queryMode').value = reportState.queryMode;
        });
    });

    // Prévia
    document.getElementById('previewBtn').addEventListener('click', showPreview);
    
    // Submit
    document.getElementById('reportBuilderForm').addEventListener('submit', onFormSubmit);
});

function addSelectedField(fieldData) {
    // Verificar se já foi adicionado
    if (reportState.selectedFields.find(f => f.field === fieldData.field)) {
        return;
    }
    
    reportState.selectedFields.push(fieldData);
    renderSelectedFields();
}

function renderSelectedFields() {
    const container = document.getElementById('selectedFieldsContainer');
    
    if (reportState.selectedFields.length === 0) {
        container.innerHTML = `
            <p class="text-muted text-center my-5">
                <i class="fas fa-hand-point-left"></i> Clique nos campos à esquerda para adicionar
                <br><small>Ou deixe vazio para SELECT * FROM tabela</small>
            </p>
        `;
        return;
    }
    
    let html = '<div class="list-group">';
    reportState.selectedFields.forEach((field, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-columns text-primary"></i>
                    <strong>${field.field}</strong>
                    <br>
                    <small class="text-muted">${field.label || field.field}</small>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSelectedField(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

function removeSelectedField(index) {
    reportState.selectedFields.splice(index, 1);
    renderSelectedFields();
}

// ========== FILTROS (WHERE) ==========
function addFilter() {
    if (!reportState.dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    const field = prompt('Nome do campo:');
    if (!field) return;
    
    const operator = prompt('Operador (=, !=, >, <, >=, <=, LIKE, IN):');
    if (!operator) return;
    
    const value = prompt('Valor:');
    if (!value) return;
    
    if (!reportState.filters) reportState.filters = [];
    reportState.filters.push({ field, operator, value });
    updateFiltersDisplay();
}

function updateFiltersDisplay() {
    const container = document.getElementById('filtersList');
    const noFilters = document.getElementById('noFilters');
    
    if (!reportState.filters || reportState.filters.length === 0) {
        noFilters.style.display = 'block';
        container.innerHTML = '';
        return;
    }
    
    noFilters.style.display = 'none';
    let html = '<div class="list-group list-group-flush">';
    reportState.filters.forEach((filter, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <small>
                    <code>${filter.field} ${filter.operator} '${filter.value}'</code>
                </small>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFilter(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('filtersJson').value = JSON.stringify(reportState.filters);
}

function removeFilter(index) {
    reportState.filters.splice(index, 1);
    updateFiltersDisplay();
}

// ========== ORDENAÇÃO (ORDER BY) ==========
function addOrderBy() {
    if (!reportState.dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    const field = prompt('Nome do campo:');
    if (!field) return;
    
    const direction = confirm('Ordenar crescente (ASC)?\nOK = ASC | Cancelar = DESC') ? 'ASC' : 'DESC';
    
    if (!reportState.orderBy) reportState.orderBy = [];
    reportState.orderBy.push({ field, direction });
    updateOrderByDisplay();
}

function updateOrderByDisplay() {
    const container = document.getElementById('orderByList');
    const noOrderBy = document.getElementById('noOrderBy');
    
    if (!reportState.orderBy || reportState.orderBy.length === 0) {
        noOrderBy.style.display = 'block';
        container.innerHTML = '';
        return;
    }
    
    noOrderBy.style.display = 'none';
    let html = '<div class="list-group list-group-flush">';
    reportState.orderBy.forEach((order, index) => {
        const icon = order.direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <small>
                    <i class="fas ${icon} text-primary"></i>
                    <strong>${order.field}</strong> <span class="badge bg-secondary">${order.direction}</span>
                </small>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeOrderBy(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('orderbyJson').value = JSON.stringify(reportState.orderBy);
}

function removeOrderBy(index) {
    reportState.orderBy.splice(index, 1);
    updateOrderByDisplay();
}

// ========== AGRUPAMENTO (GROUP BY) ==========
function addGroupBy() {
    if (!reportState.dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    const field = prompt('Nome do campo para agrupar:');
    if (!field) return;
    
    if (!reportState.groupBy) reportState.groupBy = [];
    
    // Verificar se já existe
    if (reportState.groupBy.includes(field)) {
        alert('Este campo já está no agrupamento!');
        return;
    }
    
    reportState.groupBy.push(field);
    updateGroupByDisplay();
}

function updateGroupByDisplay() {
    const container = document.getElementById('groupByList');
    const noGroupBy = document.getElementById('noGroupBy');
    
    if (!reportState.groupBy || reportState.groupBy.length === 0) {
        noGroupBy.style.display = 'block';
        container.innerHTML = '';
        return;
    }
    
    noGroupBy.style.display = 'none';
    let html = '<div class="list-group list-group-flush">';
    reportState.groupBy.forEach((field, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <small>
                    <i class="fas fa-layer-group text-primary"></i>
                    <strong>${field}</strong>
                </small>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeGroupBy(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('groupbyJson').value = JSON.stringify(reportState.groupBy);
}

function removeGroupBy(index) {
    reportState.groupBy.splice(index, 1);
    updateGroupByDisplay();
}

async function showPreview() {
    const activeTab = document.querySelector('.tab-pane.active').id;
    
    if (activeTab === 'sql-mode') {
        await showPreviewSQL();
    } else {
        await showPreviewBuilder();
    }
}

async function showPreviewBuilder() {
    const dataSource = reportState.dataSource;
    
    if (!dataSource) {
        alert('Selecione uma tabela primeiro!');
        return;
    }
    
    document.getElementById('previewCard').style.display = 'block';
    document.getElementById('previewContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Executando consulta...</p>
        </div>
    `;
    
    const formData = new FormData();
    formData.append('report_id', 'preview');
    formData.append('data_source', dataSource);
    formData.append('fields', JSON.stringify(reportState.selectedFields));
    formData.append('filters', JSON.stringify(reportState.filters || []));
    formData.append('groupby', JSON.stringify(reportState.groupBy || []));
    formData.append('orderby', JSON.stringify(reportState.orderBy || []));
    formData.append('visualization_type', document.getElementById('visualizationType').value);
    formData.append('query_mode', 'builder');
    
    console.log('📊 Executando (Builder):', {
        dataSource,
        fields: reportState.selectedFields,
        filters: reportState.filters,
        groupBy: reportState.groupBy,
        orderBy: reportState.orderBy
    });
    
    try {
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dynamic-report', {method: 'POST', body: formData});
        const result = await response.json();
        console.log('✅ Resultado:', result);
        
        if (result.success) {
            renderPreview(result);
        } else {
            showError(result.error, result.sql);
        }
    } catch (error) {
        showError(error.message);
    }
}

async function showPreviewSQL() {
    const sql = document.getElementById('customSql').value.trim();
    
    if (!sql) {
        alert('Digite uma query SQL!');
        return;
    }
    
    document.getElementById('previewCard').style.display = 'block';
    document.getElementById('previewContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Executando SQL personalizado...</p>
        </div>
    `;
    
    const formData = new FormData();
    formData.append('report_id', 'preview');
    formData.append('custom_sql', sql);
    formData.append('query_mode', 'custom_sql');
    formData.append('visualization_type', document.querySelector('[name="visualization_type_sql"]').value);
    
    console.log('📝 Executando SQL:', sql);
    
    try {
        const response = await fetch('<?= $_ENV['URL_ADM'] ?>execute-dynamic-report', {method: 'POST', body: formData});
        const result = await response.json();
        console.log('✅ Resultado:', result);
        
        if (result.success) {
            renderPreview(result);
        } else {
            showError(result.error, result.sql);
        }
    } catch (error) {
        showError(error.message);
    }
}

function renderPreview(result) {
    if (!result.data || result.data.length === 0) {
        document.getElementById('previewContent').innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> Nenhum dado encontrado
            </div>
        `;
        return;
    }
    
    const visualizationType = result.query_mode === 'custom_sql' 
        ? document.querySelector('[name="visualization_type_sql"]').value
        : document.getElementById('visualizationType').value;
    
    if (visualizationType === 'table') {
        renderTable(result.data);
    } else {
        renderChart(result.data, visualizationType);
    }
    
    document.getElementById('previewContent').insertAdjacentHTML('beforeend', `
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i> 
            ${result.rows_count} registro(s) | ${result.execution_time}s | 
            Conexão: ${result.connection_type === 'sap_b1' ? 'SAP B1 HANA' : 'Local'}
            ${result.sql ? '<br><small><code>' + result.sql + '</code></small>' : ''}
        </div>
    `);
}

function renderTable(data) {
    const headers = Object.keys(data[0]);
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm">
                <thead class="table-dark">
                    <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                </thead>
                <tbody>
    `;
    data.forEach(row => {
        html += '<tr>';
        headers.forEach(h => html += `<td>${row[h] ?? ''}</td>`);
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('previewContent').innerHTML = html;
}

function renderChart(data, type) {
    document.getElementById('previewContent').innerHTML = '<canvas id="previewChart"></canvas>';
    const labels = data.map(row => Object.values(row)[0]);
    const values = data.map(row => parseFloat(Object.values(row)[1]) || 0);
    
    new Chart(document.getElementById('previewChart'), {
        type: type.replace('_chart', ''),
        data: {
            labels: labels,
            datasets: [{
                label: 'Valores',
                data: values,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {responsive: true, maintainAspectRatio: true, aspectRatio: 2}
    });
}

function showError(error, sql = null) {
    document.getElementById('previewContent').innerHTML = `
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Erro</h5>
            <p><strong>Mensagem:</strong> ${error}</p>
            ${sql ? '<p><strong>SQL:</strong><br><code>' + sql + '</code></p>' : ''}
        </div>
    `;
}

function onFormSubmit(e) {
    const activeTab = document.querySelector('.tab-pane.active').id;
    console.log('💾 Salvando relatório - aba ativa:', activeTab);
    
    if (activeTab === 'sql-mode') {
        // Modo SQL Personalizado
        const sql = document.getElementById('customSQL').value.trim();
        console.log('📝 SQL a salvar:', sql);
        
        if (!sql) {
            e.preventDefault();
            alert('Digite o SQL personalizado!');
            return false;
        }
        
        document.getElementById('queryMode').value = 'custom_sql';
        console.log('✅ query_mode definido como: custom_sql');
    } else {
        // Modo Builder
        console.log('🔨 Modo Builder - data_source:', reportState.dataSource);
        
        if (!reportState.dataSource) {
            e.preventDefault();
            alert('Selecione uma tabela!');
            return false;
        }
        
        document.getElementById('queryMode').value = 'builder';
        document.getElementById('fieldsJson').value = JSON.stringify(reportState.selectedFields);
        document.getElementById('filtersJson').value = JSON.stringify(reportState.filters || []);
        document.getElementById('orderbyJson').value = JSON.stringify(reportState.orderBy || []);
        document.getElementById('groupbyJson').value = JSON.stringify(reportState.groupBy || []);
        
        console.log('✅ query_mode definido como: builder');
        console.log('✅ fields:', reportState.selectedFields);
        console.log('✅ filters:', reportState.filters);
        console.log('✅ orderBy:', reportState.orderBy);
        console.log('✅ groupBy:', reportState.groupBy);
    }
    
    console.log('📤 Enviando formulário...');
    return true;
}
</script>

<style>
.table-item:hover {
    background-color: #e9ecef;
}
.field-item:hover {
    background-color: #d1e7ff !important;
}
.min-height-100 {
    min-height: 100px;
}
</style>
